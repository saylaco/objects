<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Objects\Contract\DataType;
use Sayla\Objects\Contract\Mixin;
use Sayla\Objects\Contract\PropertyType;
use Sayla\Objects\Contract\ProvidesDataTypeDescriptorMixin;

class MapPropertyType implements PropertyType, ProvidesDataTypeDescriptorMixin
{
    private $autoMapping = false;

    public function disableAutoMapping()
    {
        $this->autoMapping = false;
        return $this;
    }

    public function enableAutoMapping()
    {
        $this->autoMapping = true;
        return $this;
    }

    public function getDataTypeDescriptorMixin(DataType $dataType): Mixin
    {
        return new class($dataType) implements Mixin
        {
            private $mappable;

            public function __construct(DataType $dataType)
            {
                $mappable = $dataType->getDefinedProperties(MapPropertyType::getHandle());
                $this->mappable = $mappable;
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
        };
    }

    /**
     * @return string[]
     */
    public function getDefinitionKeys(): array
    {
        return ['mapFrom', 'mapTo', 'map'];
    }

    public static function getHandle(): string
    {
        return 'map';
    }

    public function getName(): string
    {
        return self::getHandle();
    }

    public function getPropertyValue(string $attributeName, $propertyValue, string $attributeType,
                                     string $objectClass): ?array
    {
        if ($propertyValue['map'] === false) {
            return null;
        }
        if (is_array($propertyValue['map'])) {
            return [
                'attribute' => $attributeName,
                'to' => array_get($propertyValue, 'map.to', false),
                'from' => array_get($propertyValue, 'map.from', false)
            ];
        }
        if (is_string($propertyValue['map'])) {
            return [
                'attribute' => $attributeName,
                'to' => $propertyValue['map'],
                'from' => $propertyValue['map']
            ];
        }
        if (blank($propertyValue['mapTo']) && blank($propertyValue['mapFrom'])) {
            return [
                'attribute' => $attributeName,
                'to' => $attributeName,
                'from' => $attributeName
            ];
        }

        $map = ['attribute' => $attributeName, 'to' => false, 'from' => false];
        if (isset($propertyValue['mapFrom'])) {
            $map['from'] = $propertyValue['mapFrom'];
        }
        if (isset($propertyValue['mapTo'])) {
            $map['to'] = $propertyValue['mapTo'];
        }

        return $map;
    }
}