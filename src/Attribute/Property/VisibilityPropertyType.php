<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Objects\Contract\PropertyType;
use Sayla\Objects\Contract\ProvidesDataTypeDescriptorMixin;
use Sayla\Util\Mixin\Mixin;

class VisibilityPropertyType implements PropertyType, ProvidesDataTypeDescriptorMixin
{

    public function getDataTypeDescriptorMixin(string $dataType, array $properties): Mixin
    {
        return new VisibilityDescriptorMixin($properties);
    }

    /**
     * @return string[]|void
     */
    public function getDefinitionKeys(): ?array
    {
        return null;
    }

    public static function getHandle(): string
    {
        return 'visible';
    }

    public function getName(): string
    {
        return self::getHandle();
    }

    public function getPropertyValue(string $attributeName, $propertyValue, string $attributeType, string $objectClass)
    {
        return boolval($propertyValue ?? true);
    }
}