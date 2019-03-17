<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Util\Mixin\Mixin;

class MapDescriptorMixin implements Mixin
{
    private $mappable;

    public function __construct(array $properties)
    {
        $this->mappable = collect($properties);
    }

    public function isMappable($attributeName)
    {
        return $this->mappable->has($attributeName);
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
            if ($property === null) {
                $mappedData[$attributeName] = data_get($data, $attributeName);
            }
            if ($property['from']) {
                $value = data_get($data, $property['from']);
                $mappedData[$property['attribute']] = $value;
                continue;
            }
        }
        return $mappedData;
    }

    public function extract($data)
    {
        $mappedData = [];
        foreach ($data as $k => $v) {
            $property = $this->mappable [$k];
            if ($property === null || $property['to'] == false) {
                continue;
            }
            array_set($mappedData, $property['to'], $v);
        }
        return $mappedData;
    }
}