<?php

namespace Sayla\Objects\Attribute;

use Sayla\Objects\Exception\UndefinedAttribute;
use Sayla\Objects\ObjectCollection;

class ResolverFactory
{

    /**
     * @param string $attributeName
     * @return mixed|null
     */
    protected function resolveAttributeValue(string $attributeName)
    {
        $this->resolving = true;
        if ($this->hasDefaultValue($attributeName)) {
            $value = $this->getDefaultValue($attributeName);
        } else {
            $value = $this->resolveValue($attributeName, $this);
        }
        $this->setRawAttribute($attributeName, $value);
        $this->resolving = false;
        return $value;
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

    public function resolveUnknownAttribute(string $attributeName)
    {
        throw new UndefinedAttribute(get_class($this), $attributeName);
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