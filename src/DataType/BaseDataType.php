<?php

namespace Sayla\Objects\DataType;


use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Pipeline\Pipeline;
use Sayla\Objects\Attribute\Attribute;
use Sayla\Objects\Attribute\AttributeFactory;
use Sayla\Objects\Attribute\Property\MapDescriptorMixin;
use Sayla\Objects\Attribute\Property\MapPropertyType;
use Sayla\Objects\Attribute\PropertyTypeSet;
use Sayla\Objects\Contract\ProvidesDataExtraction;
use Sayla\Objects\Contract\ProvidesDataHydration;
use Sayla\Objects\Contract\ProvidesDataTypeDescriptorMixin;
use Sayla\Objects\DataObject;
use Sayla\Objects\Exception\HydrationError;
use Sayla\Objects\ObjectDispatcher;
use Sayla\Util\Mixin\MixinSet;

abstract class BaseDataType implements \Sayla\Objects\Contract\DataType
{
    /** @var array */
    protected $attributeDefinitions;
    /** @var AttributeFactory */
    protected $attributeDescriptors;
    /** @var string */
    protected $eventDispatcher;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $objectClass;
    /** @var PropertyTypeSet|\Sayla\Objects\Contract\PropertyType[] */
    protected $propertyTypes;
    /** @var \Sayla\Objects\Transformers\ValueTransformerFactory */
    protected $valueFactory;
    private $descriptor;
    private $hydrationPipeline;

    /**
     * @param $data
     * @return array
     */
    public static function convertDataToArray($data): array
    {
        if (is_object($data) && method_exists($data, 'getArrayCopy')) {
            $data = $data->getArrayCopy();
        } elseif (is_object($data) && method_exists($data, 'getAttributes')) {
            $data = $data->getAttributes();
        } elseif ($data instanceof Arrayable) {
            $data = $data->toArray();
        } else {
            $data = (array)$data;
        }
        return $data;
    }

    /**
     * @param $object
     * @return array
     * @throws \Sayla\Objects\Exception\TransformationError
     */
    public function extract($object): array
    {
        $data = self::convertDataToArray($object);
        /** @var \Sayla\Objects\DataType\AttributesContext $finalContext */
        $finalContext = $this->getExtractionPipeline($data)->thenReturn();
        $extractedData = $finalContext->attributes;
        return $extractedData;
    }

    /**
     * @param $object
     * @return array
     */
    public function extractData($object): array
    {
        /** @var \Sayla\Objects\Attribute\Property\MapDescriptorMixin $mapMixin */
        $descriptor = $this->getDescriptor();
        if (!$descriptor->hasMixin(MapDescriptorMixin::class)) {
            return self::convertDataToArray($object);
        }
        $mapMixin = $descriptor->getMixin(MapPropertyType::getHandle());
        return $mapMixin->extract($object);
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
     * @throws \Sayla\Exception\Error
     */
    public function getDefinedProperties(string $propertyType)
    {
        return $this->getAttributeDescriptors()->getAttributes()
            ->map->filterByPropertyType($propertyType)
            ->map->getFirst()
            ->filter();
    }

    /**
     * @return \Sayla\Objects\DataType\DataTypeDescriptor|\Sayla\Objects\Attribute\DefaultPropertyMixinSet
     */
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
     * @return string
     */
    public function getObjectClass(): string
    {
        return $this->objectClass;
    }

    public function getObjectDispatcher(): ObjectDispatcher
    {
        return $this->getDescriptor()->dispatcher();

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
     * @return array|mixed
     * @throws \Sayla\Exception\Error
     */
    public function hydrateData(array $data): array
    {
        /** @var \Sayla\Objects\DataType\AttributesContext $finalContext */
        $finalContext = $this->getHydrationPipeline($data)->thenReturn();
        $hydratedData = $finalContext->attributes;
        return $hydratedData;
    }

    /**
     * @param string $class
     * @param iterable $results
     * @return \Sayla\Objects\ObjectCollection|static[]
     * @throws \Sayla\Objects\Exception\HydrationError
     */
    public function hydrateMany(iterable $results)
    {
        $objectCollection = $this->getDescriptor()->newCollection();
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
            $data = self::convertDataToArray($data);
            $object->init($this->hydrateData($data));
        } catch (\Throwable $exception) {
            throw new HydrationError($this->name . ' - ' . $exception->getMessage(), $exception);
        }
        return $object;
    }

    protected function makeDataTypeDescriptor(): DataTypeDescriptor
    {
        $names = $this->getAttributeNames();
        $mixins = new MixinSet();
        foreach ($this->propertyTypes as $propertyType) {
            if ($propertyType instanceof ProvidesDataTypeDescriptorMixin) {
                $mixins[$propertyType->getName()] = $propertyType
                    ->getDataTypeDescriptorMixin(
                        $this->name,
                        $this->getProperties($propertyType->getName())->all()
                    );
            }
        }
        $descriptor = new DataTypeDescriptor($this->getName(), $this->getObjectClass(), $names, $mixins);
        if ($this->eventDispatcher) {
            $descriptor->setEventDispatcher(\Illuminate\Container\Container::getInstance()
                ->make($this->eventDispatcher));
        }
        return $descriptor;
    }

    /**
     * @return \Sayla\Objects\Attribute\PropertyTypeSet|\Sayla\Objects\Contract\PropertyType[]
     */
    public function getPropertySet(): PropertyTypeSet
    {
        return new PropertyTypeSet($this->propertyTypes->toArray());
    }

    private function getExtractionPipeline(array $attributes = [])
    {
        if (!isset($this->hydrationPipeline)) {
            $mapPipes = [];
            $pipes = [];
            foreach ($this->propertyTypes as $propertyType) {
                if (!($propertyType instanceof ProvidesDataExtraction)) {
                    continue;
                }
                if ($propertyType::getHandle() === MapPropertyType::getHandle()) {
                    $mapPipes[] = $propertyType;
                } else {
                    $pipes[] = $propertyType;
                }
            }
            $pipeline = new Pipeline();
            $this->hydrationPipeline = $pipeline->through(array_merge($mapPipes, $pipes))->via('extract');
        }
        return $this->hydrationPipeline->send(new AttributesContext($this->getDescriptor(), $attributes));
    }

    private function getHydrationPipeline(array $attributes = [])
    {
        if (!isset($this->hydrationPipeline)) {
            $mapPipes = [];
            $pipes = [];
            foreach ($this->propertyTypes as $propertyType) {
                if (!($propertyType instanceof ProvidesDataHydration)) {
                    continue;
                }
                if ($propertyType::getHandle() === MapPropertyType::getHandle()) {
                    $mapPipes[] = $propertyType;
                } else {
                    $pipes[] = $propertyType;
                }
            }
            $pipeline = new Pipeline();
            $this->hydrationPipeline = $pipeline->through(array_merge($mapPipes, $pipes))->via('hydrate');
        }
        return $this->hydrationPipeline->send(new AttributesContext($this->getDescriptor(), $attributes));
    }
}