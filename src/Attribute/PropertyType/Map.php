<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Contract\PropertyTypes\AttributePropertyType;
use Sayla\Objects\Contract\PropertyTypes\NormalizesPropertyValue;
use Sayla\Util\Mixin\Mixin;

class Map implements AttributePropertyType, NormalizesPropertyValue
{
    use SupportAutoAnnotationTrait;
    const NAME = 'map';

    public static function getProviders(): array
    {
        return [
            self::PROVIDER_HYDRATION => function ($context, callable $next) {
                $context->attributes = $context->descriptor->hydrate($context->attributes);
                return $next($context);
            },
            self::PROVIDER_EXTRACTION => function ($context, callable $next) {
                $context->attributes = $context->descriptor->extract($context->attributes);
                return $next($context);
            },
            self::PROVIDER_DESCRIPTOR_MIXIN => function (string $dataType, array $properties): Mixin {
                return new MapDescriptorMixin($properties);
            }
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPropertyValue(string $attributeName, array $value, string $attributeType): ?array
    {
        if ($value['from'] === false && $value['to'] === false) {
            return null;
        }
        if ($value['to'] === true) {
            $value['to'] = $attributeName;
        }
        if ($value['from'] === true) {
            $value['from'] = $value['to'];
        }
        return [
            'attribute' => $attributeName,
            'to' => $value['to'],
            'from' => $value['from']
        ];
    }

    public function normalizePropertyValue(array $descriptorData, string $objectClass, ?string $classFile): ?array
    {
        if (isset($descriptorData[self::NAME]) && $descriptorData[self::NAME] === false) {
            return ['to' => false, 'from' => false];
        }
        $value = ['to' => true, 'from' => true];
        if (isset($descriptorData[self::NAME]) && $descriptorData[self::NAME] === true) {
            return $value;
        }
        if (isset($descriptorData[self::NAME]) && is_array($descriptorData[self::NAME])) {
            $value = array_merge($value, $descriptorData[self::NAME]);
        }

        if (isset($descriptorData[self::NAME . 'To'])) {
            $value['to'] = $descriptorData[self::NAME . 'To'];
        }
        if (isset($descriptorData[self::NAME . 'From'])) {
            $value['from'] = $descriptorData[self::NAME . 'From'];
        }
        return $value;
    }
}