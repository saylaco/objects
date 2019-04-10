<?php

namespace Sayla\Objects\Attribute;

interface AttributePropertyType
{
    const PROVIDERS = [
        self::PROVIDER_EXTRACTION,
        self::PROVIDER_HYDRATION,
        self::PROVIDER_MIXIN,
    ];
    const PROVIDER_EXTRACTION = 'EXTRACTION';
    const PROVIDER_HYDRATION = 'HYDRATION';
    const PROVIDER_MIXIN = 'MIXIN';

    public static function getProviders(): array;

    public function getName(): string;

    public function getPropertyValue(string $attributeName, array $value, string $attributeType): ?array;
}