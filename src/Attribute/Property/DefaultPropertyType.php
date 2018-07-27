<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Objects\Contract\PropertyType;

class DefaultPropertyType implements PropertyType
{

    /**
     * @return string[]|null
     */
    public function getDefinitionKeys(): ?array
    {
        return null;
    }

    public static function getHandle(): string
    {
        return 'default';
    }

    public function getName(): string
    {
        return self::getHandle();
    }

    public function getPropertyValue(string $attributeName, $propertyValue, string $attributeType, string $objectClass)
    {
        return $propertyValue;
    }
}