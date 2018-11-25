<?php

namespace Sayla\Objects\Attribute;

use Sayla\Exception\Error;
use Sayla\Objects\Attribute\Property\Property;
use Sayla\Objects\Attribute\Property\PropertySet;
use Sayla\Objects\Attribute\Property\ResolverPropertyType;
use Sayla\Objects\Contract\AttributeResolver;
use Sayla\Objects\Contract\Property as PropertyInterface;
use Sayla\Objects\Contract\PropertyType;

class AttributeFactory
{
    const DEFAULT_ATTRIBUTE_TYPE = 'serial';
    protected $storeDefault = false;
    private $normalized = false;
    private $descriptors;
    /** @var \Sayla\Objects\Attribute\PropertyTypeSet */
    private $propertyTypeSet;
    /**
     * @var string
     */
    private $objectClass;

    public function __construct(string $objectClass, PropertyTypeSet $properties, array $definitions)
    {
        $this->objectClass = $objectClass;
        $this->descriptors = $definitions;
        $this->propertyTypeSet = $properties;
    }

    /**
     * @return string[]
     * @throws \Sayla\Exception\Error
     */
    public function getNames(): array
    {
        return $this->getAttributes()->keys()->all();
    }

    /**
     * @return \Sayla\Objects\Attribute\Attribute[]|\Illuminate\Support\Collection
     * @throws \Sayla\Exception\Error
     */
    public function getAttributes(): \Illuminate\Support\Collection
    {
        if (!$this->normalized) {
            $this->descriptors = $this->parseAttributes($this->descriptors);
            $this->normalized = true;
        }
        return collect($this->descriptors);
    }

    protected function parseAttributes(array $descriptors): array
    {
        # normalize short hand definitions that do not have properties
        $definitionsWithoutProperties = array_filter(array_keys($descriptors), 'is_int');
        if (count($definitionsWithoutProperties) > 0) {
            foreach ($definitionsWithoutProperties as $index) {
                $attributeKey = $descriptors[$index];
                unset($descriptors[$index]);
                if (!isset($descriptors[$attributeKey])) {
                    $descriptors[$attributeKey] = [];
                } else {
                    $attrName = trim(str_before($attributeKey, ':'));
                    if (isset($descriptors[$attrName])
                        && !isset($descriptors[$attrName]['type'])
                    ) {
                        $descriptors[$attrName]['type'] = trim(str_after($attributeKey, ':'));
                    }
                }
            }
        }
        $normalized = [];
        try {
            $attributeName = '[undefined]';
            # parse all attribute properties
            foreach ($descriptors as $i => $descriptorData) {
                [$attributeName, $attributeType, $descriptorData] = $this->normalize($descriptorData, $i);
                $properties = [];
                foreach ($this->propertyTypeSet as $property) {
                    $definitionKeys = $property->getDefinitionKeys();
                    if (filled($definitionKeys)) {
                        $propertyValue = [];
                        foreach ($definitionKeys as $key)
                            $propertyValue[$key] = $descriptorData[$key] ?? null;
                    } else {
                        $propertyValue = $descriptorData[$property->getName()] ?? null;
                    }
                    $propertyValue = $this->makeProperty($property, $attributeName, $attributeType, $propertyValue);
                    if ($propertyValue !== null) {
                        $properties[$property->getName()] = $propertyValue;
                    }
                }
                $descriptor = new Attribute($attributeType, $attributeName, $properties);
                $normalized[$attributeName] = $descriptor;
            }
        } catch (\Throwable $e) {
            throw new Error("Could not build definitions descriptor for \${$attributeName}", $e);
        }
        return $normalized;
    }

    /**
     * @param callable|array $definition
     * @param $attributeName
     * @return array
     */
    protected function normalize($definition, $attributeName): array
    {
        $descriptor = [];
        if ($definition instanceof \Closure || !is_array($definition) || $definition instanceof AttributeResolver) {
            $descriptor[ResolverPropertyType::getHandle()] = $definition;
        } else {
            foreach ($definition as $k => $v) {
                array_set($descriptor, $k, $v);
            }
        }
        if (isset($descriptor[ResolverPropertyType::getHandle()])
            && $descriptor[ResolverPropertyType::getHandle()] instanceof AttributeResolver) {
            $descriptor['mapTo'] = false;
        }
        if (str_contains($attributeName, ':')) {
            [$normalizedName, $normalizedType] = explode(':', trim($attributeName), 2);
        } else {
            if (!isset($normalizedName)) {
                $normalizedName = $attributeName;
            }
            $normalizedType = $descriptor['type'] ?? self::DEFAULT_ATTRIBUTE_TYPE;
        }
        return [$normalizedName, $normalizedType, $descriptor];
    }

    /**
     * @param \Sayla\Objects\Contract\PropertyType $type
     * @param $attributeName
     * @param $attributeType
     * @param $propertyValue
     * @return \Sayla\Objects\Attribute\Property\Property
     */
    protected function makeProperty(PropertyType $type, $attributeName, $attributeType,
                                    $propertyValue): ?PropertyInterface
    {
        $value = $type->getPropertyValue($attributeName, $propertyValue, $attributeType, $this->objectClass);
        if ($value === null) {
            return null;
        }
        if (!$value instanceof PropertyInterface) {
            if (is_array($value)) {
                return new PropertySet($type::getHandle(), $type->getName(), $value);
            } else {
                return new Property($type::getHandle(), $type->getName(), $value);
            }
        }
        return $value;
    }

}