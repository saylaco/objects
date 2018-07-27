<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Objects\Contract\PropertyType;

class VisibilityPropertyType implements PropertyType
{

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