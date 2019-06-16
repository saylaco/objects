<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Contract\PropertyTypes\AttributePropertyType;
use Sayla\Util\Mixin\Mixin;

class DefaultValue implements AttributePropertyType
{
    const NAME = 'default';

    public static function getProviders(): array
    {
        return [
            self::PROVIDER_HYDRATION => function ($context, callable $next) {
                $defaults = array_diff_key(
                    $context->descriptor->getDefaultValues(),
                    array_filter($context->attributes)
                );
                if (filled($defaults)) {
                    $context->attributes = array_merge($context->attributes, $defaults);
                }
                return $next($context);
            },
            self::PROVIDER_DESCRIPTOR_MIXIN => function (string $dataType, array $properties): Mixin {
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