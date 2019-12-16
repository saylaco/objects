<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Contract\PropertyTypes\AttributePropertyType;
use Sayla\Objects\Contract\PropertyTypes\NormalizesPropertyValue;

class Type implements AttributePropertyType, NormalizesPropertyValue
{
    const DEFAULT_TYPE = 'string';
    const IDENTITY_PROPERTIES = [self::NAME, 'varType'];
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
        $type = $value['type'] ?? $value['value'] ?? self::DEFAULT_TYPE;
        return [
            'type' => $type,
            'varType' => $value['varType'] ?? $type
        ];
    }

    public function normalizePropertyValue(array $descriptorData, string $objectClass, ?string $classFile): ?array
    {
        return array_merge(
            ['type' => null, 'varType' => null],
            array_only($descriptorData, self::IDENTITY_PROPERTIES)
        );
    }
}