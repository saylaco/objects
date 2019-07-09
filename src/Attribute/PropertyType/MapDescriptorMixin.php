<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Util\Mixin\Mixin;

class MapDescriptorMixin implements Mixin
{
    private $mappable;

    public function __construct(array $properties)
    {
        $this->mappable = collect($properties);
    }

    public function extract($data)
    {
        $mappedData = [];
        foreach ($data as $k => $v) {
            $property = $this->mappable->get($k);
            if ($property === null || $property['to'] === false) {
                continue;
            }
            array_set($mappedData, $property['to'], $v);
        }
        return $mappedData;
    }

    public function getAttributeMapping($key)
    {
        return $this->mappable[$key];
    }

    public function getHydrationMap()
    {
        $keyByRawName = [];
        foreach ($this->mappable as $attributeName => $property) {
            if ($property['from']) {
                $keyByRawName[$property['from']] = $property['attribute'];
                continue;
            }
        }
        return $keyByRawName;
    }

    /**
     * @return array
     */
    public function getMappable(): array
    {
        return $this->mappable->keys()->sort()->all();
    }

    public function hydrate($data)
    {
        $mappedData = [];
        foreach ($this->mappable as $attributeName => $property) {
            if ($property['from']) {
                $value = data_get($data, $property['from']);
                $mappedData[$property['attribute']] = $value;
                continue;
            }
        }
        return $mappedData;
    }

    public function isMappable($attributeName)
    {
        return $this->mappable->has($attributeName);
    }
}