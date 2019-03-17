<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Objects\Contract\PropertyType;
use Sayla\Objects\Contract\ProvidesDataTypeDescriptorMixin;
use Sayla\Util\Mixin\Mixin;

class AccessPropertyType implements PropertyType, ProvidesDataTypeDescriptorMixin
{

    public static function getHandle(): string
    {
        return 'access';
    }

    public function getDataTypeDescriptorMixin(string $dataType, array $properties): Mixin
    {
        return new AccessDescriptorMixin($properties);
    }

    /**
     * @return string[]|array
     */
    public function getDefinitionKeys(): ?array
    {
        return ['writable', 'readable'];
    }

    public function getName(): string
    {
        return self::getHandle();
    }

    public function getPropertyValue(string $attributeName, $propertyValue, string $attributeType, string $objectClass)
    {
        return [
            'writable' => $propertyValue['writable'] ?? true,
            'readable' => $propertyValue['readable'] ?? true
        ];
    }
}