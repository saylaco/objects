<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Objects\Contract\PropertyType;
use Sayla\Objects\Contract\ProvidesDataExtraction;
use Sayla\Objects\Contract\ProvidesDataHydration;
use Sayla\Objects\Contract\ProvidesDataTypeDescriptorMixin;
use Sayla\Util\Mixin\Mixin;

class MapPropertyType implements PropertyType, ProvidesDataTypeDescriptorMixin, ProvidesDataHydration, ProvidesDataExtraction
{
    private $autoMapping = false;

    public static function getHandle(): string
    {
        return 'map';
    }

    public function disableAutoMapping()
    {
        $this->autoMapping = false;
        return $this;
    }

    public function enableAutoMapping()
    {
        $this->autoMapping = true;
        return $this;
    }

    public function getDataTypeDescriptorMixin(string $dataType, array $properties): Mixin
    {
        return new MapDescriptorMixin($properties);
    }

    /**
     * @return string[]
     */
    public function getDefinitionKeys(): array
    {
        return ['mapFrom', 'mapTo', 'map'];
    }

    public function getName(): string
    {
        return self::getHandle();
    }

    public function getPropertyValue(string $attributeName, $propertyValue, string $attributeType,
                                     string $objectClass): ?array
    {
        if ($propertyValue['map'] === false) {
            return null;
        }
        if (is_array($propertyValue['map'])) {
            return [
                'attribute' => $attributeName,
                'to' => array_get($propertyValue, 'map.to', false),
                'from' => array_get($propertyValue, 'map.from', false)
            ];
        }
        if (is_string($propertyValue['map'])) {
            return [
                'attribute' => $attributeName,
                'to' => $propertyValue['map'],
                'from' => $propertyValue['map']
            ];
        }
        if (blank($propertyValue['mapTo']) && blank($propertyValue['mapFrom'])) {
            return [
                'attribute' => $attributeName,
                'to' => $attributeName,
                'from' => $attributeName
            ];
        }

        $map = ['attribute' => $attributeName, 'to' => false, 'from' => false];
        if (isset($propertyValue['mapFrom'])) {
            $map['from'] = $propertyValue['mapFrom'];
        }
        if (isset($propertyValue['mapTo'])) {
            $map['to'] = $propertyValue['mapTo'];
        }

        return $map;
    }

    /**
     * @param \Sayla\Objects\DataType\AttributesContext $context
     * @param callable $next
     * @return \Sayla\Objects\DataType\AttributesContext
     */
    public function hydrate($context, callable $next)
    {
        $context->attributes = $context->descriptor->hydrate($context->attributes);
        return $next($context);
    }

    /**
     * @param \Sayla\Objects\DataType\AttributesContext $context
     * @param callable $next
     * @return \Sayla\Objects\DataType\AttributesContext
     */
    public function extract($context, callable $next)
    {
        $context->attributes = $context->descriptor->extract($context->attributes);
        return $next($context);
    }
}