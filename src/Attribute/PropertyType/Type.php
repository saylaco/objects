<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Attribute\AttributePropertyType;

class Type implements AttributePropertyType
{
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
            'type' => $value['type'] ?? $value['value'] ?? 'string',
        ];
    }
}