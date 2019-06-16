<?php

namespace Sayla\Objects\Contract\PropertyTypes;

use Sayla\Objects\Contract\Storable;

interface AttributePropertyType
{
    const ON_AFTER_CREATE = Storable::ON_AFTER_CREATE;
    const ON_AFTER_DELETE = Storable::ON_AFTER_DELETE;
    const ON_AFTER_UPDATE = Storable::ON_AFTER_UPDATE;
    const ON_BEFORE_CREATE = Storable::ON_BEFORE_CREATE;
    const ON_BEFORE_DELETE = Storable::ON_BEFORE_DELETE;
    const ON_BEFORE_UPDATE = Storable::ON_BEFORE_UPDATE;
    const PROVIDER_DESCRIPTOR_MIXIN = 'DESCRIPTOR_MIXIN';
    const PROVIDER_EXTRACTION = 'EXTRACTION';
    const PROVIDER_HYDRATION = 'HYDRATION';

    public static function getProviders(): array;

    public function getName(): string;

    public function getPropertyValue(string $attributeName, array $value, string $attributeType): ?array;
}