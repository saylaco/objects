<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Data\DotArray;
use Sayla\Exception\Error;
use Sayla\Objects\Attribute\AttributePropertyType;
use Sayla\Objects\Attribute\Property;
use Sayla\Objects\Contract\ModifiesAttributeDescriptor;
use Sayla\Objects\Contract\NormalizesPropertyValue;

class PropertyTypeFactory
{
    /**
     * @param \ReflectionClass $objectClass
     * @param string $attributeName
     * @param string $attributeType
     * @param array $descriptorData
     * @return array
     * @throws \Sayla\Exception\Error
     */
    public function getProperties(string $objectClass, ?string $classFile, string $attributeName, string $attributeType,
                                  array $descriptorData): array
    {

        /** @var AttributePropertyType[] $propertyTypes */
        $propertyTypes = array_merge(
            $this->getAutoPropertyTypes($objectClass),
            $this->getPropertyTypes(array_keys($descriptorData))
        );
        if (isset($propertyTypes['type'])) {
            // make sure type is first property type
            $typeProp = $propertyTypes['type'];
            unset($propertyTypes['type']);
            $propertyTypes = ['type' => $typeProp] + $propertyTypes;
        }
        /** @var ModifiesAttributeDescriptor[] $modifyingPropTypes */
        $modifyingPropTypes = [];
        $normalizedValues = [];
        foreach ($propertyTypes as $key => $propertyType) {
            if ($propertyType instanceof ModifiesAttributeDescriptor) {
                $modifyingPropTypes[] = $propertyType;
            }
            if ($propertyType instanceof NormalizesPropertyValue) {
                $value = $propertyType->normalizePropertyValue($descriptorData, $objectClass, $classFile);
            } elseif (!isset($descriptorData[$key])) {
                $value = ['value' => null];
            } elseif (!is_array($descriptorData[$key])) {
                $value = ['value' => $descriptorData[$key]];
            } else {
                $value = $descriptorData[$key];
            }
            if ($value === null) {
                continue;
            }
            $normalizedValues[$propertyType->getName()] = $value;
        }

        $properties = [];
        foreach ($normalizedValues as $key => $value) {
            $propertyType = $propertyTypes[$key];
            $value = $propertyType->getPropertyValue($attributeName, $value, $attributeType);
            if ($value === null) {
                continue;
            }
            if ($key === Type::NAME) {
                $attributeType = $value['type'];
            }

            $properties[$propertyType->getName()] = new Property($propertyType->getName(), $value);
        }
        if (filled($modifyingPropTypes)) {
            $modifiedPropertyValues = new DotArray();
            foreach ($modifyingPropTypes as $propertyType) {
                $key = $propertyType->getName();
                if (!isset($properties[$key])) continue;
                $newValue = $propertyType->modifyDescriptor($properties[$key]->getValue(), $normalizedValues);
                if ($newValue === null) continue;
                $modifiedPropertyValues->fill($newValue);
            }
            if (!$modifiedPropertyValues->isEmpty()) {
                foreach ($modifiedPropertyValues as $propertyName => $overriddenValue) {
                    $propertyType = $propertyTypes[$propertyName];
                    $value = $propertyType->getPropertyValue(
                        $attributeName,
                        array_merge($properties[$propertyName]->getValue(), $overriddenValue),
                        $attributeType
                    );
                    if ($value === null) {
                        unset($properties[$propertyName]);
                    }
                    $properties[$propertyName] = new Property($propertyName, $value);
                }
            }
        }
        return array_values($properties);
    }

    public function getPropertyType(string $key): AttributePropertyType
    {
        switch ($key) {
            case Type::NAME:
                return new Type();
            case Access::NAME:
            case in_array($key, Access::IDENTITY_PROPERTIES):
                return new Access();
            case DefaultValue::NAME:
                return new DefaultValue();
            case Map::NAME:
                return new Map();
            case Resolver::NAME:
                return new Resolver();
            case Transform::NAME:
                return new Transform();
        }
        throw new Error('Attribute not supported - ' . $key);
    }

    /**
     * @param array $propertyValues
     * @return AttributePropertyType[]
     * @throws \Sayla\Exception\Error
     */
    public function getPropertyTypes(array $propertyTypeNames): array
    {
        $properties = [];
        foreach ($propertyTypeNames as $key) {
            $propertyType = $this->getPropertyType($key);
            $properties[$propertyType->getName()] = $propertyType;
        }
        return $properties;
    }

    public function getProviders(array $keys): array
    {
        $providers = [];
        foreach ($keys as $key) {
            switch ($key) {
                case Access::NAME:
                case in_array($key, Access::IDENTITY_PROPERTIES):
                    $providers[Access::NAME] = Access::getProviders();
                    break;
                case DefaultValue::NAME:
                    $providers[$key] = DefaultValue::getProviders();
                    break;
                case Map::NAME:
                    $providers[$key] = Map::getProviders();
                    break;
                case Resolver::NAME:
                    $providers[$key] = Resolver::getProviders();
                    break;
                case Transform::NAME:
                    $providers[$key] = Transform::getProviders();
                    break;
            }
        }

        if (!isset($providers[Access::NAME])) {
            $providers[Access::NAME] = Access::getProviders();
        }
        if (!isset($providers[Resolver::NAME])) {
            $providers[Resolver::NAME] = Resolver::getProviders();
        }
        if (!isset($providers[Transform::NAME])) {
            $providers[Transform::NAME] = Transform::getProviders();
        }
        return $providers;
    }

    /**
     * @param string $objectClass
     * @return AttributePropertyType[]
     */
    private function getAutoPropertyTypes(string $objectClass): array
    {
        $propertyTypes = [Access::NAME => new Access(), Resolver::NAME => new Resolver()];
        $autoMap = Map::applyAutomatically($objectClass) || true;
        if ($autoMap) {
            $_map = new Map();
            $propertyTypes[$_map->getName()] = $_map;
        }

        $autoTransform = Transform::applyAutomatically($objectClass) || true;
        if ($autoTransform) {
            $transform = new Transform();
            $propertyTypes[$transform->getName()] = $transform;
        }

        return $propertyTypes;
    }
}