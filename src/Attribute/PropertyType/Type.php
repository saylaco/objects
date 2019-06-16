<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Contract\PropertyTypes\AttributePropertyType;

class Type implements AttributePropertyType
{
    const NAME = 'type';
    const DEFAULT_TYPE = 'string';

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