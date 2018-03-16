<?php

namespace Sayla\Objects\Transformers;

use Sayla\Exception\Error;

class Transformer
{
    /** @var \Sayla\Objects\Transformers\ValueFactory */
    private static $factory;
    /** @var string[] */
    private static $aliases = [
        'fk' => 'databaseKey',
        'pk' => 'databaseKey',
        'integer' => 'int',
    ];
    /**
     * Definitions for transforming data types
     * @var mixed[][]
     */
    protected $options = [];
    protected $skipNonAttributes = false;
    protected $skipObjectSmashing = false;
    protected $contexts = [];
    private $valueTransformers = [];

    /**
     * Transformer constructor.
     * @param iterable $allOptions
     */
    public function __construct(iterable $allOptions = [])
    {
        $this->options = [];
        foreach ($allOptions as $name => $options) {
            $this->addAttribute($name, $options);
        }
    }

    /**
     * @param string $name
     * @param array $optionsArray
     * @throws \ErrorException
     */
    public function addAttribute(string $name, array $optionsArray): void
    {
        if (!isset($optionsArray['type'])) {
            throw new \ErrorException('Transformation type property must be set: '
                . $name . ' = ' . varExport($optionsArray));
        }
        $type = $optionsArray['type'];
        $options = new Options($optionsArray);
        if (isset(self::$aliases[$type])) {
            $options['type'] = self::$aliases[$type];
        } else {
            $options['type'] = $type;
        }
        if ($options->alias) {
            $aliasOptions = $optionsArray;
            $aliasOptions['aliasOf'] = $name;
            $this->options[$options->alias] = new Options($aliasOptions);
        }
        $this->options[$name] = $options;
    }

    /**
     * @param string $valueTransformerClass
     * @param string|null $typeName
     * @throws \Sayla\Exception\Error
     */
    public static function addType(string $valueTransformerClass, string $typeName)
    {
        self::getFactory()->addType($valueTransformerClass, $typeName);
    }

    public static function getFactory(): ValueFactory
    {
        return self::$factory;
    }

    public static function setFactory(ValueFactory $resolver)
    {
        self::$factory = $resolver;
    }

    /**
     * @param array $attributes
     * @param array $context
     * @return array
     */
    public function buildAll($attributes, ...$context)
    {
        if (count($this->options) == 0) {
            return $attributes;
        }
        $this->pushContext($context);
        foreach ($attributes as $k => $v)
            $attributes[$k] = $this->build($k, $v);
        $this->popContext();
        return $attributes;
    }

    /**
     * @param $context
     * @return mixed
     */
    public function pushContext(array $context): void
    {
        $this->contexts[] = $context;
    }

    /**
     * @param string $key
     * @param $value
     * @param array $context
     * @return mixed
     * @throws \Sayla\Exception\Error
     */
    public function build(string $key, $value = null, ...$context)
    {
        $hasContext = func_num_args() > 2;
        try {
            if ($hasContext) {
                $this->pushContext($context);
            }
            if ($this->isNotTransformable($key)) {
                return $value;
            }
            return $this->callBuilder($key, $value);
        } catch (\Throwable $exception) {
            throw (new Error(trim('Failed transformation of "' . $key . '" ' . $exception->getMessage()), $exception))
                ->withContext('value', $value);
        } finally {
            if ($hasContext) {
                $this->popContext();
            }
        }
    }

    /**
     * @param $key
     * @return bool
     */
    protected function isNotTransformable($key): bool
    {
        return ($this->skipNonAttributes && !$this->isAttribute($key))
            || ($this->isAttribute($key)
                && $this->skipObjectSmashing
                && in_array($this->getAttributeOptions($key)->type, ['objectCollection', 'object']));
    }

    /**
     * @param string $attr
     * @return bool
     */
    public function isAttribute(string $attr): bool
    {
        return isset($this->options[$attr]);
    }

    /**
     * @param string $attr
     * @return Options|Options[]
     * @throws \ErrorException
     */
    public function getAttributeOptions(string $attr = null)
    {
        if ($attr) {
            if (!$this->isAttribute($attr)) {
                throw new \ErrorException('Transformer is not configured for "' . $attr . '"');
            }
            return $this->options[$attr];
        }
        return $this->options;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function callBuilder(string $key, $value)
    {
        $context = $this->getContext();
        array_unshift($context, $value);
        return call_user_func_array([$this->getValueTransformer($key), 'build'], $context);
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return end($this->contexts) ?: [];
    }

    public function getValueTransformer($key): ValueTransformer
    {
        if (isset($this->valueTransformers[$key])) {
            return $this->valueTransformers[$key];
        }
        $options = $this->getAttributeOptions($key);
        return $this->valueTransformers[$key] = self::getFactory()->getTransformer($options->type, $options);
    }

    public function popContext(): void
    {
        array_pop($this->contexts);
    }

    /**
     * @param array $attributes
     * @param array $context
     * @return array
     */
    public function buildOnly(array $attributes, ...$context): array
    {
        if (count($this->options) == 0) {
            return $attributes;
        }
        $built = [];
        $this->pushContext($context);
        foreach ($attributes as $k => $v) {
            if (isset($this->options[$k])) {
                $built[$k] = $this->build($k, $v);
            }
        }
        $this->popContext();
        return $built;
    }

    /**
     * @param string $attributeName
     * @return \Closure
     */
    public function getBuildCallable(string $attributeName)
    {
        return function ($value, ...$args) use ($attributeName) {
            if ($hasContext = count($args) > 0) {
                $this->pushContext($args);
            }
            try {
                return $this->callBuilder($attributeName, $value);
            } finally {
                if ($hasContext) {
                    $this->popContext();
                }
            }
        };
    }

    /**
     * @param string $attributeName
     * @return \Closure
     */
    public function getSmashCallable(string $attributeName)
    {
        return function ($value) use ($attributeName) {
            return $this->callSmasher($attributeName, $value);
        };
    }

    /**
     * @param string $key
     * @param $value
     * @return mixed
     */
    public function callSmasher(string $key, $value)
    {
        $context = $this->getContext();
        array_unshift($context, $value);
        return call_user_func_array([$this->getValueTransformer($key), 'smash'], $context);
    }

    /**
     * @return \Illuminate\Support\Collection|\Sayla\Objects\Transformers\ValueTransformer[]
     */
    public function getValueTransformers()
    {
        $transformers = [];
        foreach ($this->getAttributeNames() as $key) {
            $transformers[$key] = $this->getValueTransformer($key);
        }
        return collect($transformers);
    }

    /**
     * @return string[]
     */
    public function getAttributeNames(): array
    {
        return array_keys($this->options);
    }

    /**
     * @return bool
     */
    public function isSkipNonAttributes(): bool
    {
        return $this->skipNonAttributes;
    }

    /**
     * @param bool $skipNonAttributes
     * @return Transformer
     */
    public function skipNonAttributes(bool $skipNonAttributes = true): self
    {
        $this->skipNonAttributes = $skipNonAttributes;
        return $this;
    }

    public function skipObjectSmashing(bool $skipObjectSmashing = true)
    {
        $this->skipObjectSmashing = $skipObjectSmashing;
        return $this;
    }

    /**
     * @param array $attributes
     * @param array $context
     * @return array
     */
    public function smashAll($attributes, ...$context)
    {
        if (count($this->options) == 0) {
            return $attributes;
        }
        if (count($context) > 0) {
            $this->pushContext($context);
        }
        foreach ($attributes as $k => $v) {
            $attributes[$k] = $this->smash($k, $v);
        }
        if (count($context) > 0) {
            $this->popContext();
        }
        return $attributes;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param array $context
     * @return mixed|null
     */
    public function smash(string $key, $value = null, ...$context)
    {
        $hasContext = func_num_args() > 2;
        try {
            if ($hasContext) {
                $this->pushContext($context);
            }

            if ($this->isNotTransformable($key)) {
                return $value;
            }
            return $this->callSmasher($key, $value);
        } catch (\Throwable $e) {
            throw (new Error(trim('Failed transformation of "' . $key . '". ' . $e->getMessage()), $e))
                ->withContext('value', $value);
        } finally {
            if ($hasContext) {
                $this->popContext();
            }
        }
    }

    /**
     * @param array $attributes
     * @param array $context
     * @return array
     */
    public function smashOnly(array $attributes, ...$context): array
    {
        if (count($this->options) == 0) {
            return $attributes;
        }
        $smashed = [];
        $this->pushContext($context);
        foreach ($attributes as $k => $v) {
            if (isset($this->options[$k])) {
                $smashed[$k] = $this->smash($k, $v);
            }
        }
        $this->popContext();
        return $smashed;
    }

}