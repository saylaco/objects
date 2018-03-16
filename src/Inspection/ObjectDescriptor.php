<?php

namespace Sayla\Objects\Inspection;

use Sayla\Objects\AttributableObject;
use Sayla\Objects\DataObject;
use Sayla\Objects\ObjectCollection;
use Sayla\Objects\Transformers\Transformer;

class ObjectDescriptor
{
    /** @var string|\Sayla\Objects\DataObject */
    public $class;
    /** @var string */
    public $name;
    public $definitions = [];
    public $transformations = [];
    /** @var bool[] */
    public $readable = [];
    /** @var bool[] */
    public $writable = [];
    /** @var bool[] */
    public $visible = [];
    public $loadable = [];
    public $resolves = [];
    public $resolvesMany = [];
    public $rules = [];
    public $createRules = [];
    public $updateRules = [];
    public $deleteRules = [];
    public $validationMessages = [];
    public $labels = [];
    public $defaults = [];
    public $relations = [];
    public $aliases = [];
    public $getFilters = [];
    public $setFilters = [];
    /** @var array */
    public $storeTransformations;
    public $hasNestedAttributes = [];
    public $autoResolves = [];
    public $nonPersistentAttributes = [];
    public $keys = [];
    private $isAbstract;
    private $defaultValues;

    /**
     * AttributeProperties constructor.
     * @param $class
     */
    public function __construct(string $name, string $class = null)
    {
        $this->name = $name;
        $this->class = $class ?? DataObject::class;
    }

    /**
     * @param string $aliasName
     * @return array
     */
    public function getAlias(string $aliasName): array
    {
        return $this->aliases[$aliasName];
    }

    public function getAttributeNames()
    {
        return array_keys($this->definitions);
    }

    public function getDefaultValues(): array
    {
        if (isset($this->defaultValues)) {
            return $this->defaultValues;
        }
        $this->defaultValues = [];
        foreach ($this->defaults as $k => $v) {
            $this->defaultValues[$k] = value($v);
        }
        return $this->defaultValues;
    }

    public function getGetFilters($attributeName)
    {
        return $this->getFilters[$attributeName] ?? [];
    }

    public function getKeys(): array
    {
        return $this->keys;
    }

    public function getObjectClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $attributeName
     * @return array
     */
    public function getRelation(string $attributeName): array
    {
        return $this->relations[$attributeName];
    }

    /**
     * @param \string[] $attributeNames
     * @return array
     */
    public function getRelations(string ...$attributeNames): array
    {
        if (is_array($attributeNames[0])) {
            $attributeNames = $attributeNames[0];
        }
        return array_only($this->relations, $attributeNames);
    }

    public function getSetFilters($attributeName)
    {
        return $this->setFilters[$attributeName] ?? [];
    }

    /**
     * @return Transformer
     */
    public function getTransformer(): Transformer
    {
        return new Transformer($this->transformations);
    }

    public function getVisible()
    {
        return array_keys(array_filter($this->visible));
    }

    public function hasNestedAttributes(string $attributeName)
    {
        return array_get($this->hasNestedAttributes, $attributeName) != null;
    }

    public function hasResolver(string $attributeName)
    {
        return $this->resolvesMany[$attributeName] != false || $this->resolves[$attributeName] != false;
    }

    public function isAlias($attributeName)
    {
        return isset($this->aliases[$attributeName]);
    }

    public function isHidden(string $attributeName)
    {
        return $this->visible[$attributeName] == false;
    }

    public function isLoadable(string $attributeName)
    {
        return $this->loadable[$attributeName];
    }

    public function isReadable(string $attributeName)
    {
        return $this->readable[$attributeName] ?? $this->isAttribute($attributeName);
    }

    /**
     * @param $attributeName
     * @return bool
     */
    public function isAttribute($attributeName)
    {
        return isset($this->definitions[$attributeName]);
    }

    public function isRelation($attributeName)
    {
        return isset($this->relations[$attributeName]);
    }

    public function isWritable(string $attributeName)
    {
        return $this->writable[$attributeName] ?? $this->isAttribute($attributeName);
    }

    /**
     * @param $attributes
     * @return DataObject
     * @throws \Sayla\Exception\Error
     */
    public function makeObject($attributes)
    {
        $object = $this->newInstance();
        $object->setDescriptor($this->name);
        $this->class::unguarded(function () use ($attributes, $object) {
            $object->initStoreData($attributes);
            $object->trigger('newInstance', $attributes);
        });
        return $object;
    }

    /**
     * @return \Sayla\Objects\DataObject
     */
    protected function newInstance(): DataObject
    {
        $objectClass = $this->class;
        if ($this->isClassAbstract()) {
            eval("\$object = new class() extends {$objectClass} {
             public static function getDefinedAttributes (): array {return [];}
             };");
        } else {
            $object = new $objectClass;
        }
        return $object;
    }

    /**
     * @return bool
     */
    protected function isClassAbstract(): bool
    {
        if (isset($this->isAbstract)) {
            return $this->isAbstract;
        }
        $reflection = new \ReflectionClass($this->class);
        return $this->isAbstract = $reflection->isAbstract();
    }

    public function pluck(string $property): array
    {
        return array_pluck($this->definitions, $property, 'name');
    }

    public function remapAttributesForObject($attributes)
    {
        $attrNameMap = $this->getHydrationAttributeMap();
        $storeAttrNames = array_keys($attrNameMap);
        $objectAttrNames = array_values($attrNameMap);
        if ($storeAttrNames != $objectAttrNames) {
            $renamedAttributes = [];
            foreach ($storeAttrNames as $i => $storeAttrName) {
                $value = data_get($attributes, $storeAttrName);
                if ($value !== null) {
                    $renamedAttributes[$objectAttrNames[$i]] = $value;
                }
            }
            return $renamedAttributes;
        }
        return $attributes;

    }

    public function getHydrationAttributeMap(): array
    {
        return array_pluck($this->definitions, 'name', 'storeAs');
    }

    /**
     * @param \Sayla\Objects\AttributableObject $object
     * @return array
     * @throws \ErrorException
     */
    public function remapAttributesForStore(AttributableObject $object, array $attributeList = null): array
    {
        $attributes = $this->getPersistentAttributes($object);
        $values = [];
        $transformer = $this->getStoreTransformer()->skipNonAttributes()->skipObjectSmashing();
        $optionsMap = $transformer->getAttributeOptions();
        foreach ($optionsMap as $persistableName => $options) {
            if (isset($options->aliasOf) || $options->type == 'relation') continue;
            if (isset($options->aliasOf)) continue;
            $objectProperty = $options->name;
            if (!$this->definitions[$objectProperty]['store']) continue;
            if (isset($attributeList) && !in_array($objectProperty, $attributeList)) continue;
            $recordProperty = $this->definitions[$objectProperty]['storeAs'];
            if (!isset($attributes[$objectProperty])) {
                if ($this->hasDefaultValue($objectProperty)) {
                    array_set(
                        $values,
                        $recordProperty,
                        $transformer->smash($persistableName, $this->getDefaultValue($objectProperty))
                    );
                } elseif ($this->definitions[$objectProperty]['store']) {
                    array_set($values, $recordProperty, null);
                }
            } else {
                array_set(
                    $values,
                    $recordProperty,
                    $transformer->smash($persistableName, $attributes[$objectProperty])
                );
            }
        }
        return $values;
    }

    /**
     * @param AttributableObject $object
     * @return array
     */
    public function getPersistentAttributes(AttributableObject $object)
    {
        $keys = array_merge(array_keys($this->aliases), $this->nonPersistentAttributes);
        $attributes = $object->toArray();
        return count($keys) > 0 ? array_except($attributes, $keys) : $attributes;
    }

    /**
     * @return Transformer
     */
    public function getStoreTransformer(): Transformer
    {
        $transformations = collect($this->storeTransformations)->filter();
        return new Transformer($transformations->all());
    }

    public function hasDefaultValue(string $attributeName): bool
    {
        return isset($this->defaults[$attributeName]);
    }

    public function getDefaultValue(string $attributeName)
    {
        return value($this->defaults[$attributeName]);
    }

    public function resolveValue(string $attributeName, DataObject $object)
    {
        if (!$this->isAttribute($attributeName)) {
            return $object->resolveUnknownAttribute($attributeName);
        }
        if ($this->hasSingleResolver($attributeName)) {
            return call_user_func($this->getSingleResolver($attributeName), $object);
        } elseif ($this->hasManyResolver($attributeName)) {
            $values = call_user_func($this->getManyResolver($attributeName), new ObjectCollection([$object]));
            return end($values);
        }
        return null;
    }

    public function hasSingleResolver(string $attributeName)
    {
        return $this->resolves[$attributeName] != false;
    }

    public function getSingleResolver(string $attributeName)
    {
        return $this->resolves[$attributeName];
    }

    public function hasManyResolver(string $attributeName)
    {
        return $this->resolvesMany[$attributeName] != false;
    }

    public function getManyResolver(string $attributeName)
    {
        return $this->resolvesMany[$attributeName];
    }

    public function resolveValues(string $attributeName, ObjectCollection $objects): array
    {
        if (!$this->isAttribute($attributeName)) {
            return $objects->map->resolveUnknownAttribute($attributeName)->all();
        }
        if ($this->hasManyResolver($attributeName)) {
            return call_user_func($this->getManyResolver($attributeName), $objects);
        }
        if ($this->hasSingleResolver($attributeName)) {
            $singleResolver = $this->getSingleResolver($attributeName);
            $values = [];
            foreach ($objects as $i => $object) {
                $values[$i] = call_user_func($singleResolver, $object);
            }
            return $values;
        }
        return array_combine($objects->keys()->all(), array_fill(0, count($objects), null));
    }
}