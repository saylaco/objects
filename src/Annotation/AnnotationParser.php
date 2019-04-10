<?php

namespace Sayla\Objects\Annotation;

use Illuminate\Support\Arr;

class AnnotationParser
{
    const ANNOTATION_REGEX = '/@(\w+)(?:\s*(?:\(\s*)?(.*?)(?:\s*\))?)??\s*(?:\n|\*\/)/';
    const NESTED_VALUE = '/\s*(([\w\.]+)\((.*)\))/';
    const PARAMETER_REGEX = '/([\w\.]+)\s*=\s*(\[[^\]]*\]|"[^"]*"|[^,)]*)\s*(?:,|$)/';
    const PRIMARY_VALUE_REGEX = '/^(' . self::SINGLE_VALUE_PATTERN . '(?:,)).*/';
    const SINGLE_VALUE_PATTERN = '((["\w]+)(?:\:\s*([\\\"\w]+)|\s*))';
    const SINGLE_VALUE_REGEX = '/^' . self::SINGLE_VALUE_PATTERN . '$/';
    protected static $sharedResolvers = [];
    protected $resolvers = [];

    public static function addSharedResolver(string $name, callable $annotationResolver)
    {
        self::$sharedResolvers[strtolower($name)] = $annotationResolver;
    }

    public static function addSharedResolverClass(string $name, string $annotationResolver)
    {
        self::$sharedResolvers[strtolower($name)] = $annotationResolver;
    }

    public function addResolver(string $name, callable $annotationResolver)
    {
        $this->resolvers[strtolower($name)] = $annotationResolver;
        return $this;
    }

    public function addResolverClass(string $name, string $annotationResolver)
    {
        $this->resolvers[strtolower($name)] = $annotationResolver;
        return $this;
    }

    /**
     * Parse any annotations from the given doc comment and return them in an
     * array structure.  The annotations in the returned array are indexed by
     * their lowercased name.  Parameters with a value defined as a comma
     * separated list contained in braces will be return as arrays.  Parameter
     * values defined in quotes will have the quotes stripped and the inner value
     * parsed for boolean and numeric values.  If not a boolean or numeric value,
     * will be return as a string.    All other parameter values will be returned as
     * either a boolean, number or string as appropriate.
     *
     * @param string $docComment The comment to parse.
     * @return array Array containing the defined annotations.
     */
    public function getAnnotations($docComment): ParserResult
    {
        $hasAnnotations = preg_match_all(
            self::ANNOTATION_REGEX,
            $docComment,
            $matches,
            PREG_SET_ORDER
        );

        $annotations = new ParserResult();
        if ($hasAnnotations) {
            foreach ($matches as $anno) {
                $annoName = strtolower($anno[1]);
                $annotations[] = $this->parseBody($annoName, $anno[2]);
            }
        }
        return $annotations;
    }

    /**
     * @return array
     */
    protected function getResolvers(): array
    {
        $resolvers = array_merge(self::$sharedResolvers, $this->resolvers);
        return $resolvers;
    }

    /**
     * @param $raw
     * @param $singleValue
     * @param $primary
     * @return array
     */
    protected function parseBody(string $name, $raw): AnnoEntry
    {
        $raw = trim($raw);
        $value = true;
        $modifier = null;
        $properties = [];
        $hasSingleValue = preg_match(self::SINGLE_VALUE_REGEX, $raw, $singleValue);
        $hasPrimaryValue = preg_match(self::PRIMARY_VALUE_REGEX, $raw, $primary);
         //dump(compact('name', 'raw', 'hasPrimaryValue', 'hasSingleValue'), 'AA --- ' . __METHOD__);
        if ($hasSingleValue) {
            $value = $this->parseValue($singleValue[2]);
            $modifier = isset($singleValue[3]) ? self::parseValue($singleValue[3]) : null;
        } elseif ($hasPrimaryValue) {
            $value = self::parseValue($primary[3]);
            $modifier = isset($primary[4]) ? self::parseValue($primary[4]) : null;
            $primaryRaw = str_replace($primary[1], '', $raw);
            $properties = $this->parseParams($primaryRaw);
        } elseif (strlen($raw) > 0) {
            if (str_contains($raw, ['(', ')', '='])) {
                $properties += $this->parseParams($raw);
            } else {
                $value = $raw;
            }
        }
         //dump(compact('name', 'modifier', 'properties', 'value'), 'BB --- ' . __METHOD__);
        $resolvers = $this->getResolvers();
        if (isset($resolvers[$name])) {
            if (is_string($resolvers[$name])) {
                $resolverClass = $resolvers[$name];
                return new $resolverClass($name, $value, $modifier, $properties);
            }
            return call_user_func($resolvers[$name], $name, $value, $modifier, $properties);
        }
        return new AnnoEntry($name, $value, $modifier, $properties);
    }

    /**
     * @param $raw
     * @return array
     */
    protected function parseParams(string $raw): array
    {
        $raw = trim($raw);
        $val = [];
        $hasNestedValues = preg_match_all(self::NESTED_VALUE, $raw, $nestedValues, PREG_SET_ORDER);
        $hasParams = preg_match_all(self::PARAMETER_REGEX, $raw, $params, PREG_SET_ORDER);
        if ($hasNestedValues) {
            foreach ($nestedValues as $nestedValue) {
                Arr::set($val, $nestedValue[2], $this->parseParams($nestedValue[3]));
                $raw = str_replace($nestedValue[0], '', $raw);
            }
        } else if ($hasParams) {
            foreach ($params as $param) {
                Arr::set($val, $param[1], self::parseValue($param[2]));
                $raw = str_replace($param[0], '', $raw);
            }
        }
        if (strlen($raw) > 0) {
            $val += self::parseParams($raw);
        }
        return $val;
    }

    protected function parseValue($value)
    {
        $val = trim(trim($value), "\"");
        if (substr($val, 0, 1) == '[' && substr($val, -1) == ']') {
            // Array values
            $vals = explode(',', substr($val, 1, -1));
            $val = [];
            foreach ($vals as $v) {
                $val[] = self::parseValue($v);
            }
            return $val;

        } else if (substr($val, 0, 1) == '"' && substr($val, -1) == '"') {
            // Quoted value, remove the quotes then recursively parse and return
            $val = substr($val, 1, -1);
            return self::parseValue($val);

        } else if (strtolower($val) == 'true') {
            // Boolean value = true
            return true;

        } else if (strtolower($val) == 'false') {
            // Boolean value = false
            return false;

        } else if (is_numeric($val)) {
            // Numeric value, determine if int or float and then cast
            if ((float)$val == (int)$val) {
                return (int)$val;
            } else {
                return (float)$val;
            }

        } else {
            // Nothing special, just return as a string
            return $val;
        }
    }
}