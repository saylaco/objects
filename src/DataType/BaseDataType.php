<?php

namespace Sayla\Objects\DataType;


use Sayla\Objects\Attribute\Attribute;
use Sayla\Objects\Attribute\AttributeFactory;
use Sayla\Objects\Attribute\Property\AccessPropertyType;
use Sayla\Objects\Attribute\Property\DefaultPropertyType;
use Sayla\Objects\Attribute\Property\MapPropertyType;
use Sayla\Objects\Attribute\Property\ResolverPropertyType;
use Sayla\Objects\Attribute\Property\VisibilityPropertyType;
use Sayla\Objects\Attribute\PropertyTypeSet;
use Sayla\Objects\Contract\ProvidesDataTypeDescriptorMixin;
use Sayla\Objects\DataObject;
use Sayla\Objects\Exception\HydrationError;
use Sayla\Objects\ObjectCollection;
use Sayla\Objects\ObjectDispatcher;
use Sayla\Objects\Transformers\Transformer;
use Sayla\Util\Mixin\MixinSet;

abstract class BaseDataType implements \Sayla\Objects\Contract\DataType
{
    /** @var array */
    protected $attributeDefinitions;
    /** @var AttributeFactory */
    protected $attributeDescriptors;
    /** @var ObjectDispatcher */
    protected $dispatcher;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $objectClass;
    protected $propertyTypes;
    /** @var \Sayla\Objects\Transformers\ValueTransformerFactory */
    protected $valueFactory;
    private $descriptor;

    /**
     * @param $object
     * @return array
     * @throws \Sayla\Objects\Exception\TransformationError
     */
    public function extract($object): array
    {
        $transformer = $this->getTransformer();
        $mapping = $this->getDefinedProperties(MapPropertyType::getHandle());
        $mappedData = [];
        foreach ($object as $k => $v) {
            $property = $mapping[$k];
            if ($property === null || $property['to'] == false) {
                continue;
            }
            array_set($mappedData, $property['to'], $transformer->smash($property['attribute'], $v));
        }
        return $mappedData;
    }

    /**
     * @param $object
     * @return array
     */
    public function extractData($object): array
    {
        $mapping = $this->getDefinedProperties(MapPropertyType::getHandle());
        $mappedData = [];
        foreach ($mapping as $property) {
            if ($property === null || $property['to'] == false) {
                continue;
            }
            array_set($mappedData, $property['to'], data_get($object, $property['attribute']));
        }
        return $mappedData;
    }

    /**
     * @param string $attribute
     * @return Attribute
     */
    public function getAttributeDescriptor(string $attribute): Attribute
    {
        return $this->getAttributeDescriptors()->getAttributes()[$attribute];
    }

    /**
     * @return AttributeFactory
     */
    public function getAttributeDescriptors(): AttributeFactory
    {
        return $this->attributeDescriptors ?? $this->attributeDescriptors = new AttributeFactory(
                $this->objectClass,
                $this->propertyTypes,
                $this->attributeDefinitions
            );
    }

    /**
     * @return string[]|array
     */
    public function getAttributeNames(): array
    {
        return $this->getAttributeDescriptors()->getNames();
    }

    /**
     * @param string $propertyType
     * @return \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[]
     */
    public function getDefinedProperties(string $propertyType)
    {
        return $this->getAttributeDescriptors()->getAttributes()
            ->map->filterByPropertyType($propertyType)
            ->map->getFirst()
            ->filter();
    }

    public function getDescriptor(): DataTypeDescriptor
    {
        return $this->descriptor ?? ($this->descriptor = $this->makeDataTypeDescriptor());
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param \Sayla\Objects\DataObject|array $object
     * @return array
     */
    protected function getNonResolvableAttributes($object): array
    {
        $array = is_array($object) ? $object : $object->toArray();
        return array_except($array, $this->getDescriptor()->getResolvable());
    }

    /**
     * @return string
     */
    public function getObjectClass(): string
    {
        return $this->objectClass;
    }

    public function getObjectDispatcher(): ObjectDispatcher
    {
        return $this->dispatcher;
    }

    /**
     * @param string $propertyType
     * @return \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[]
     */
    public function getProperties(string $propertyType)
    {
        return $this->getAttributeDescriptors()->getAttributes()
            ->map->filterByPropertyType($propertyType)
            ->map->getFirst();
    }

    /**
     * @return array|\Sayla\Objects\Attribute\DefaultPropertyTypeSet
     */
    public function getPropertySet(): PropertyTypeSet
    {
        return new PropertyTypeSet($this->propertyTypes->toArray());
    }

    /**
     * @return \Sayla\Objects\Transformers\Transformer
     */
    public function getTransformer(): \Sayla\Objects\Transformers\Transformer
    {
        $transformations = $this->getDefinedProperties('transform');
        $transformations = array_except($transformations, $this->getDescriptor()->getResolvable());
        $transformer = new Transformer($transformations->map->getValue());
        if (isset($this->valueFactory)) {
            $transformer->setFactory($this->valueFactory);
        }
        return $transformer;
    }

    /**
     * Hydrate $object with the provided $data.
     *
     * @param iterable $data
     * @return \Sayla\Objects\DataObject
     * @throws \Sayla\Objects\Exception\HydrationError
     */
    public function hydrate($data)
    {
        return $this->hydrateObject(new $this->objectClass, $data);
    }

    /**
     * @param iterable $data
     * @return array|mixed
     * @throws \Sayla\Exception\Error
     */
    public function hydrateData($data)
    {
        $mapping = $this->getDefinedProperties(MapPropertyType::getHandle());
        $mappedData = [];
        foreach ($mapping as $property) {
            if ($property === null) {
                $mappedData[$property['attribute']] = data_get($data, $property['attribute']);
            }
            if ($property['from']) {
                $value = data_get($data, $property['from']);
                $mappedData[$property['attribute']] = $value;
                continue;
            }
        }
        $mappedData = array_merge($this->getDescriptor()->getDefaultValues(), $mappedData);
        $attributes = array_filter($mappedData);
        return $this->getTransformer()->skipNonAttributes()->buildAll($attributes);
    }

    /**
     * @param string $class
     * @param iterable $results
     * @return \Sayla\Objects\ObjectCollection|static[]
     * @throws \Sayla\Objects\Exception\HydrationError
     */
    public function hydrateMany(iterable $results)
    {
        $objectCollection = $this->newCollection();
        foreach ($results as $i => $result) {
            $objectCollection[$i] = $this->hydrate($result);
        }
        return $objectCollection;
    }

    /**
     * Hydrate $object with the provided $data.
     *
     * @param iterable $data
     * @return \Sayla\Objects\DataObject
     * @throws \Sayla\Objects\Exception\HydrationError
     */
    public function hydrateObject(DataObject $object, $data)
    {
        try {
            $object->setDataType($this->name);
            if (is_iterable($data)) {
                $transformedData = $this->hydrateData($data);
            } else {
                $transformedData = $this->hydrateData((array)$data);
            }
            $object->init($transformedData);
        } catch (\Throwable $exception) {
            throw new HydrationError($this->name . ' - ' . $exception->getMessage(), $exception);
        }
        return $object;
    }

    protected function makeDataTypeDescriptor(): DataTypeDescriptor
    {
        $names = $this->getAttributeNames();
        $dataTypeDescriptor = new DataTypeDescriptor(
            $this->getObjectDispatcher(),
            $this->getName(),
            $this->getObjectClass(),
            $this->getDefinedProperties(ResolverPropertyType::getHandle()),
            collect(array_combine($names, $names)),
            $this->getProperties(AccessPropertyType::getHandle()),
            $this->getProperties(VisibilityPropertyType::getHandle()),
            $this->getDefinedProperties(DefaultPropertyType::getHandle()),
            null
        );
        $mixins = new MixinSet();
        foreach ($this->getPropertySet() as $propertyType) {
            if ($propertyType instanceof ProvidesDataTypeDescriptorMixin) {
                $mixins[$propertyType->getName()] = $propertyType->getDataTypeDescriptorMixin($this);
            }
        }
        $dataTypeDescriptor->setMixins($mixins);
        return $dataTypeDescriptor;
    }

    public function newCollection()
    {
        return ObjectCollection::makeObjectCollection($this->name);
    }
}