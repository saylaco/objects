<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Attribute\AttributePropertyType;
use Sayla\Util\Mixin\Mixin;

class DefaultValue implements AttributePropertyType
{
    const NAME = 'default';

    public static function getProviders(): array
    {
        return [
            self::PROVIDER_HYDRATION => function ($context, callable $next) {
                $mappedData = array_merge($context->descriptor->getDefaultValues(), $context->attributes);
                $context->attributes = array_filter($mappedData);
                return $next($context);
            },
            self::PROVIDER_MIXIN => function (string $dataType, array $properties): Mixin {
                return new DefaultDescriptorMixin(array_filter($properties));
            }
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPropertyValue(string $attributeName, array $value, string $attributeType): ?array
    {
        return ['defaultValue' => $value['value'] ?? null];
    }
}