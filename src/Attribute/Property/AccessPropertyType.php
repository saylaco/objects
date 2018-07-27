<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Objects\Contract\PropertyType;

class AccessPropertyType implements PropertyType
{
    /**
     * @return string[]|array
     */
    public function getDefinitionKeys(): ?array
    {
        return ['writable', 'readable'];
    }

    public static function getHandle(): string
    {
        return 'access';
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