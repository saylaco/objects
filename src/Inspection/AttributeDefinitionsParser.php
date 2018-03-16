<?php

namespace Sayla\Objects\Inspection;

use Illuminate\Support\Str;
use Sayla\Exception\Error;
use Sayla\Exception\InvalidValue;
use Sayla\Objects\Contract\AttributeResolver;
use Sayla\Objects\Resolvers\AliasResolver;

class AttributeDefinitionsParser
{
    const DEFAULT_ATTRIBUTE_TYPE = 'serial';
    /** @var  callable */
    protected $callback;
    protected $storeDefault = false;

    /**
     * AttributeDefinitionsParser constructor.
     * @param callable $callback
     */
    public function __construct(callable $callback = null)
    {
        $this->callback = $callback;
    }

    /**
     * @param \Sayla\Objects\Inspection\ObjectDescriptor $descriptor
     * @param array $attributeProperties
     * @throws \Sayla\Exception\Error
     */
    public function parse(ObjectDescriptor $descriptor, array $attributeProperties): void
    {
        $reflection = isset($descriptor->class) && class_exists($descriptor->class)
            ? new \ReflectionClass($descriptor->class)
            : null;
        # normalize short hand attributes that do not have properties
        $attributesWithoutProperties = array_filter(array_keys($attributeProperties), 'is_int');
        if (count($attributesWithoutProperties) > 0) {
            foreach ($attributesWithoutProperties as $index) {
                $attributeKey = $attributeProperties[$index];
                unset($attributeProperties[$index]);
                if (!isset($attributeProperties[$attributeKey])) {
                    $attributeProperties[$attributeKey] = [];
                } else {
                    $attrName = trim(str_before($attributeKey, ':'));
                    if (isset($attributeProperties[$attrName])
                        && !isset($attributeProperties[$attrName]['type'])
                    ) {
                        $attributeProperties[$attrName]['type'] = trim(str_after($attributeKey, ':'));
                    }
                }
            }
        }
        # parse all attribute properties
        foreach ($attributeProperties as $i => $attribute) {
            try {
                $attribute = $this->parseAttribute(
                    $descriptor,
                    $this->normalizeProperties($attribute, $i),
                    $reflection
                );
                if (isset($this->callback)) {
                    call_user_func_array($this->callback, [$attribute, $descriptor, $reflection]);
                }
                $descriptor->definitions[$attribute['name']] = $attribute;
            } catch (\Throwable $e) {
                throw new Error(
                    "Could not build attributes descriptor for {$descriptor->class}[{$i}] - {$e->getMessage()}",
                    $e
                );
            }
        }
        # define default key
        if (empty($descriptor->keys) && $descriptor->isAttribute('id')) {
            $descriptor->keys[] = 'id';
        }
    }

    /**
     * @param \Sayla\Objects\Inspection\ObjectDescriptor $descriptor
     * @param array $attribute
     * @return array
     */
    protected function parseAttribute(ObjectDescriptor $descriptor, array $attribute,
                                      \ReflectionClass $reflection = null)
    {
        if (isset($attribute['default'])) {
            $descriptor->defaults[$attribute['name']] = $attribute['default'];
        }
        $descriptor->visible[$attribute['name']] = $attribute['visible'] ?? true;
        $descriptor->writable[$attribute['name']] = $attribute['writable'] ?? true;
        $descriptor->readable[$attribute['name']] = $attribute['readable'] ?? true;
        $descriptor->setFilters[$attribute['name']] = (array)($attribute['onSet'] ?? []);
        $descriptor->getFilters[$attribute['name']] = (array)($attribute['onGet'] ?? []);

        if (!empty($attribute['key'])) {
            $descriptor->keys[] = $attribute['name'];
        }

        if (isset($attribute['transform']['type'])) {
            $attribute = $this->parseTransformations($descriptor, $attribute);
        }
        array_set($descriptor->autoResolves, $attribute['name'], !empty($attribute['autoResolve']));

        $attribute = $this->parseValidationRules($descriptor, $attribute);

        $attribute = $this->parseStoreNames($descriptor, $attribute);

        if ($attribute['store'] === false) {
            $descriptor->nonPersistentAttributes[$attribute['name']] = $attribute['name'];
        }

        if ($reflection) {
            $resolver = array_pull($attribute, 'resolver');
            if (is_string($resolver)) {
                if (starts_with($resolver, '@')) {
                    // create a alias resolver:
                    // @getRealValue => $object->getRealValue($attributeName)
                    $resolver = new AliasResolver(substr($resolver, 1) . '(' . varExport($attribute['name']) . ')');
                } else {
                    // create a alias resolver:
                    // SomeClassDefiningInvoke => $resolver($attributeName)
                    $resolver = new $resolver;
                }
            }
            $attribute = $this->parseResolvers($descriptor, $reflection, $attribute, $resolver);
        }

        return $attribute;
    }

    /**
     * @param \Sayla\Objects\Inspection\ObjectDescriptor $descriptor
     * @param $attribute
     * @return mixed
     */
    protected function parseTransformations(ObjectDescriptor $descriptor, $attribute): array
    {
        $transform = array_pull($attribute, 'transform', []);

        if (!is_array($transform)) {
            throw new InvalidValue('Transform rules must be an array - ' . $descriptor->class . '.' . $attribute['name']);
        }

        if (!isset($transform['type'])) {
            $transform['type'] = $attribute['type'];
        }
        $transform['objectProperty'] = $attribute['name'];

        $descriptor->transformations[$attribute['name']] = $transform;

        $attribute['storeAs'] = $attribute['storeAs'] ?? $attribute['name'];
        if ($attribute['store'] === true) {
            $descriptor->storeTransformations[$attribute['storeAs']] = array_merge($transform, [
                'type' => $attribute['type'],
                'name' => $attribute['name']
            ]);
        } elseif ($attribute['store'] === false) {
            $descriptor->storeTransformations[$attribute['storeAs']] = $transform;
            $descriptor->storeTransformations[$attribute['storeAs']]['name'] = $attribute['storeAs'];
        } else {
            $storeValue = array_get($attribute, 'store');
            if (is_array($storeValue)) {
                $storeTransform = array_merge($transform, $storeValue);
            } else {
                $storeTransform = $transform;
            }
            $storeTransform['name'] = $attribute['storeAs'];
            $storeTransform['objectProperty'] = $attribute['name'];
            $storeTransform = $this->normalizeProperties($storeTransform, $attribute['storeAs']);
            $descriptor->storeTransformations[$attribute['storeAs']] = $storeTransform;
        }
        return $attribute;
    }

    /**
     * @param $properties
     * @param $attributeName
     * @return array
     */
    protected function normalizeProperties($properties, $attributeName): array
    {
        if ($properties instanceof AttributeResolver) {
            $properties = ['resolver' => $properties];
        }
        if (!is_array($properties)) {
            $attributeName = (string)$properties;
            $properties = [];
        }
        $defaultAttributeType = self::DEFAULT_ATTRIBUTE_TYPE;
        if (str_contains($attributeName, ':')) {
            [$properties['name'], $defaultAttributeType] = explode(':', trim($attributeName), 2);
        } else {
            if (!isset($properties['name'])) {
                $properties['name'] = $attributeName;
            }
        }
        if (!isset($properties['type'])) {
            $properties['type'] = $defaultAttributeType;
        }
        if (empty($properties['transform'])
            || (!empty($properties['transform']) && !isset($properties['transform']['type']))) {
            $properties['transform']['type'] = $properties['type'];
        }
        if (!isset($properties['store'])) {
            $properties['store'] = (!empty($properties['storeAs'])) ? true : $this->storeDefault;
        }
        return $properties;
    }

    /**
     * @param \Sayla\Objects\Inspection\ObjectDescriptor $descriptor
     * @param array $attributes
     * @return array
     */
    protected function parseValidationRules(ObjectDescriptor $descriptor, array $attributes): array
    {
        $descriptor->labels[$attributes['name']] = $attributes['label'] ?? $this->toTitle($attributes['name']);
        $descriptor->rules[$attributes['name']] = $this->normalizeValidationRules($attributes['rules'] ?? null);
        if (isset($attributes['errMsg'])) {
            $descriptor->validationMessages[$attributes['name']] = $attributes['errMsg'];
        }
        $descriptor->deleteRules[$attributes['name']] = $this->normalizeValidationRules($attributes['deleteRules'] ?? null);
        $descriptor->updateRules[$attributes['name']] = $this->normalizeValidationRules($attributes['updateRules'] ?? null);
        $descriptor->createRules[$attributes['name']] = $this->normalizeValidationRules($attributes['createRules'] ?? null);
        return $attributes;
    }

    /**
     * @param $attributeName
     * @return string
     */
    protected function toTitle($attributeName): string
    {
        return Str::title(str_replace(['-', '_'], ' ', $attributeName));
    }

    /**
     * @param $rules
     * @return array
     */
    protected function normalizeValidationRules($rules): array
    {
        if (is_string($rules)) {
            return explode('|', $rules);
        }
        return $rules ?? [];
    }

    /**
     * @param \Sayla\Objects\Inspection\ObjectDescriptor $descriptor
     * @param $attribute
     * @return mixed
     */
    protected function parseStoreNames(ObjectDescriptor $descriptor, $attribute)
    {
        $attribute['storeAs'] = $attribute['storeAs'] ?? $attribute['name'];
        if (isset($attribute['storeIn'])) {
            $attribute['storeAs'] = $attribute['storeIn'] . '.' . $attribute['storeAs'];
        }
        if (isset($attribute['storeIn'])) {
            array_set($descriptor->hasNestedAttributes, $attribute['storeIn'], $attribute['name']);
        }
        return $attribute;
    }

    /**
     * @param \Sayla\Objects\Inspection\ObjectDescriptor $descriptor
     * @param \ReflectionClass $reflection
     * @param array $attribute
     * @param \Sayla\Objects\Contract\AttributeResolver|null $resolver
     * @return array
     * @throws \Sayla\Exception\Error
     */
    protected function parseResolvers(ObjectDescriptor $descriptor,
                                      \ReflectionClass $reflection,
                                      array $attribute,
                                      AttributeResolver $resolver = null)
    {
        if ($resolver) {
            $resolver->setOwnerAttributeName($attribute['name']);
            $resolver->setOwnerObjectClass($attribute['owner'] ?? $reflection->name);
            $descriptor->resolves[$attribute['name']] = [$resolver, 'resolve'];
            $descriptor->resolvesMany[$attribute['name']] = [$resolver, 'resolveMany'];
        } else {
            $attribute = $this->parseManyResolver($descriptor, $reflection, $attribute);
            $attribute = $this->parseSingleResolver($descriptor, $reflection, $attribute);
        }
        return $attribute;
    }

    /**
     * @param \Sayla\Objects\Inspection\ObjectDescriptor $descriptor
     * @param \ReflectionClass $reflection
     * @param array $attribute
     * @return array
     */
    protected function parseManyResolver(ObjectDescriptor $descriptor, \ReflectionClass $reflection,
                                         array $attribute): array
    {
        if ($reflection->hasMethod($method = 'resolve' . studly_case($attribute['name']) . 'Attributes')) {
            if (!$reflection->getMethod($method)->isStatic()) {
                throw new Error($reflection->name . '::' . $method . ' must be static');
            }
            $descriptor->resolvesMany[$attribute['name']] = $reflection->name . '::' . $method;
        } else {
            $descriptor->resolvesMany[$attribute['name']] = false;
        }
        return $attribute;
    }

    /**
     * @param \Sayla\Objects\Inspection\ObjectDescriptor $descriptor
     * @param \ReflectionClass $reflection
     * @param array $attribute
     * @return array
     * @throws \Sayla\Exception\Error
     */
    protected function parseSingleResolver(ObjectDescriptor $descriptor, \ReflectionClass $reflection,
                                           array $attribute): array
    {
        if ($reflection->hasMethod($method = 'resolve' . studly_case($attribute['name']) . 'Attribute')) {
            if (!$reflection->getMethod($method)->isStatic()) {
                throw new Error($reflection->name . '::' . $method . ' must be static');
            }
            $descriptor->resolves[$attribute['name']] = $reflection->name . '::' . $method;
        } else {
            $descriptor->resolves[$attribute['name']] = false;
        }
        return $attribute;
    }

    /**
     * @param \Sayla\Objects\Inspection\ObjectDescriptor $descriptor
     * @param array $attribute
     * @return array
     */
    protected function parseRelation(ObjectDescriptor $descriptor, array $attribute)
    {
        $relation = $attribute['relation'];
        $relation['objectProperty'] = $attribute['name'];
        if (!isset($relation['owner'])) {
            $relation['owner'] = $descriptor->class;
        }
        $descriptor->relations[$attribute['name']] = $relation;
        $attribute['store'] = false;
        return $attribute;
    }
}