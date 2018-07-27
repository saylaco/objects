<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Objects\Contract\PropertyType;

class RelationPropertyType implements PropertyType
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
        $relation = $attribute['relation'];
        $relation['objectProperty'] = $attribute['name'];
        if (!isset($relation['owner'])) {
            $relation['owner'] = $descriptor->class;
        }
        $descriptor->relations[$attribute['name']] = $relation;
        $attribute['store'] = false;
        return $attribute;
    }
}