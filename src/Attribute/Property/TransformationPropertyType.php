<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Objects\Contract\PropertyType;
use Sayla\Objects\Exception\PropertyError;

class TransformationPropertyType implements PropertyType
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
        return 'transform';
    }

    public function getName(): string
    {
        return self::getHandle();
    }

    public function getPropertyValue(string $attributeName, $propertyValue, string $attributeType, string $objectClass)
    {
        if (empty($propertyValue)) {
            $propertyValue = [];
        } elseif (!is_array($propertyValue)) {
            throw new PropertyError('Transform rules must be an array - ' . $attributeName);
        } else $transform = $propertyValue;
        $transform['type'] = array_pull($propertyValue, 'type', $attributeType);
        $propertyValue['options'] = $propertyValue;
        return $transform;
    }
}