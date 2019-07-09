<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Contract\PropertyTypes\AttributePropertyType;

class Type implements AttributePropertyType
{
    const DEFAULT_TYPE = 'string';
    const NAME = 'type';

    public static function getProviders(): array
    {
        return [

        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPropertyValue(string $attributeName, array $value, string $attributeType): array
    {
        return [
            'type' => $value['type'] ?? $value['value'] ?? self::DEFAULT_TYPE,
        ];
    }
}