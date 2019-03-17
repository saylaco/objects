<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Objects\Contract\PropertyType;
use Sayla\Objects\Contract\ProvidesDataHydration;
use Sayla\Objects\Contract\ProvidesDataTypeDescriptorMixin;
use Sayla\Util\Mixin\Mixin;

class DefaultPropertyType implements PropertyType, ProvidesDataTypeDescriptorMixin, ProvidesDataHydration
{

    public static function getHandle(): string
    {
        return 'default';
    }

    public function getDataTypeDescriptorMixin(string $dataType, array $properties): Mixin
    {
        return new DefaultDescriptorMixin(array_filter($properties));
    }

    /**
     * @return string[]|null
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
        return $propertyValue;
    }

    public function hydrate($context, callable $next)
    {
        $mappedData = array_merge($context->descriptor->getDefaultValues(), $context->attributes);
        $context->attributes = array_filter($mappedData);
        return $next($context);
    }
}