<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Objects\Contract\PropertyType;
use Sayla\Objects\Contract\ProvidesDataExtraction;
use Sayla\Objects\Contract\ProvidesDataHydration;
use Sayla\Objects\Contract\ProvidesDataTypeDescriptorMixin;
use Sayla\Objects\Exception\PropertyError;
use Sayla\Util\Mixin\Mixin;

class TransformationPropertyType
    implements PropertyType, ProvidesDataTypeDescriptorMixin, ProvidesDataHydration, ProvidesDataExtraction
{

    public static function getHandle(): string
    {
        return 'transform';
    }

    public function getDataTypeDescriptorMixin(string $dataType, array $properties): Mixin
    {
        return new TransformationDescriptorMixin($properties);
    }

    /**
     * @return string[]|void
     */
    public function getDefinitionKeys(): ?array
    {
        return null;
    }

    public function getName(): string
    {
        return self::getHandle();
    }

    public function getPropertyValue(string $attributeName, $propertyValue, string $attributeType, string $objectClass)
    {
        if (empty($propertyValue)) {
            $propertyValue = [];
        } elseif (!is_array($propertyValue)) {
            throw new PropertyError('Transform rules must be an array - ' . $attributeName);
        } else $transform = $propertyValue;
        $transform['type'] = array_pull($propertyValue, 'type', $attributeType);
        $propertyValue['options'] = $propertyValue;
        return $transform;
    }

    public function hydrate($context, callable $next)
    {
        /** @var \Sayla\Objects\Attribute\Property\TransformationDescriptorMixin $mixin */
        $mixin = $context->descriptor->getMixin($this->getName());
        $excluded = $context->descriptor->hasMixin(ResolverDescriptorMixin::class)
            ? $context->descriptor->getResolvable()
            : null;
        $transformer = $mixin->getTransformer($excluded);
        $context = $next($context);
        $context->attributes = $transformer->skipNonAttributes()->buildAll($context->attributes);
        return $context;
    }

    /**
     * @param \Sayla\Objects\DataType\AttributesContext $context
     * @param callable $next
     * @return \Sayla\Objects\DataType\AttributesContext
     * @throws \Sayla\Objects\Exception\TransformationError
     */
    public function extract($context, callable $next)
    {
        /** @var \Sayla\Objects\Attribute\Property\TransformationDescriptorMixin $mixin */
        $mixin = $context->descriptor->getMixin($this->getName());
        $transformer = $mixin->getTransformer();
        foreach ($context->attributes as $k => $v) {
            $context->attributes[$k] = $transformer->smash($k, $v);
        }
        return $context;
    }
}