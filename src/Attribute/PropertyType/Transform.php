<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Attribute\AttributePropertyType;
use Sayla\Util\Mixin\Mixin;

class Transform implements AttributePropertyType
{
    use SupportAutoAnnotationTrait;
    const NAME = 'transform';

    public static function getProviders(): array
    {
        return [
            self::PROVIDER_HYDRATION => function ($context, callable $next) {
                /** @var TransformationDescriptorMixin|\Sayla\Objects\DataType\DataTypeDescriptor $descriptor */
                $descriptor = $context->descriptor;
                $excluded = $descriptor->hasMixin(ResolverDescriptorMixin::class) ? $descriptor->getResolvable() : null;
                $transformer = $descriptor->getTransformer($excluded);
                $context = $next($context);
                $context->attributes = $transformer->skipNonAttributes()->buildAll($context->attributes);
                return $context;
            },
            self::PROVIDER_EXTRACTION => function ($context, callable $next) {
                /** @var TransformationDescriptorMixin|\Sayla\Objects\DataType\DataTypeDescriptor $descriptor */
                $descriptor = $context->descriptor;
                $transformer = $descriptor->getTransformer()->skipNonAttributes();
                foreach ($context->attributes as $k => $v) {
                    $context->attributes[$k] = $transformer->smash($k, $v);
                }
                return $next($context);
            },
            self::PROVIDER_MIXIN => function (string $dataType, array $properties): Mixin {
                return new TransformationDescriptorMixin($properties);
            }
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param \ReflectionProperty $property
     * @return array|null
     * @throws \Sayla\Objects\Exception\PropertyError
     */
    public function getPropertyValue(string $attributeName, array $value, string $attributeType): ?array
    {
        if (!isset($value['type'])) {
            $value['type'] = $attributeType;
        }
        return $value;
    }

}