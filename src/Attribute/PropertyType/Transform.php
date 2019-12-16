<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Contract\DataObject\StorableObject;
use Sayla\Objects\Contract\IDataObject;
use Sayla\Objects\Contract\PropertyTypes\AttributePropertyType;
use Sayla\Objects\DataType\DataTypeManager;
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
                $attributes = $transformer->smashAll($context->attributes);
                $context->attributes = $attributes;
                return $next($context);
            },
            self::PROVIDER_DESCRIPTOR_MIXIN => function (string $dataType, array $properties): Mixin {
                return new TransformationDescriptorMixin($properties);
            },
            self::ON_BEFORE_CREATE => function (StorableObject $object) {
                $transformer = $object::descriptor()->getOnCreateTransformer();
                $newValues = $transformer->skipNonAttributes()->buildOnly($object->toArray());
                $object->fill($newValues);
            },
            self::ON_BEFORE_UPDATE => function (StorableObject $object) {
                $transformer = $object::descriptor()->getOnUpdateTransformer();
                $newValues = $transformer->skipNonAttributes()->buildOnly($object->toArray());
                $object->fill($newValues);
            },
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param \ReflectionProperty $property
     * @return array|null
     * @throws \Sayla\Objects\Contract\Exception\PropertyError
     */
    public function getPropertyValue(string $attributeName, array $value, string $attributeType): ?array
    {
        if (!isset($value['type'])) {
            $isArray = ends_with($attributeType, '[]');
            $possibleDataObjectName = $isArray ? str_before($attributeType, '[]') : $attributeType;
            if (
                is_subclass_of($possibleDataObjectName, IDataObject::class, true)
                || DataTypeManager::resolve()->has($possibleDataObjectName)
            ) {
                $value['type'] = $isArray ? 'objectCollection' : 'object';
                $value['dataType'] = $possibleDataObjectName;
            } else {
                $value['type'] = $attributeType;
            }
        }
        if (!isset($value['varType'])) {
            $value['varType'] = null;
        }
        return $value;
    }

}