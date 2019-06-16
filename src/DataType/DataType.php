<?php

namespace Sayla\Objects\DataType;

use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use Sayla\Objects\Attribute\AttributeFactory;
use Sayla\Objects\Contract\Stores\ObjectStore;
use Sayla\Objects\DataObject;
use Sayla\Objects\Contract\Exception\HydrationError;
use Sayla\Objects\SimpleEventDispatcher;
use Sayla\Objects\StorableTrait;
use Throwable;

final class DataType
{
    /** @var AttributeFactory */
    protected $attributes;
    /**
     * @var string
     */
    protected $baseObjectClass;
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
    protected $storeOptions;
    private $descriptor;
    private $extractionPipeline;
    private $hydrationPipeline;
    private $interfaces;
    /** @var callable */
    private $storeResolver;
    private $traits;

    public function __construct(array $options)
    {
        $this->eventDispatcher = SimpleEventDispatcher::class;
        $this->objectClass = $options['objectClass'];
        $this->name = $options['name'];
        $this->storeOptions = $options['store'] ?? null;
        $this->traits = $options['traits'];
        $this->interfaces = $options['interfaces'];
        $this->attributes = self::makeAttributeFactory($options);
        $this->descriptor = self::makeDescriptor($this->attributes, $options['name'], $this->eventDispatcher);
    }

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
     * @param array $options
     * @return \Sayla\Objects\Attribute\AttributeFactory
     */
    public static function makeAttributeFactory(array $options): AttributeFactory
    {
        return new AttributeFactory($options['objectClass'], $options['attributes'], $options['classFile']);
    }

    public static function makeDescriptor(AttributeFactory $attributeFactory, string $name,
                                          string $eventDispatcher = null): DataTypeDescriptor
    {
        $names = $attributeFactory->getNames();
        $mixins = $attributeFactory->getMixins();
        $descriptor = new DataTypeDescriptor($name, $attributeFactory->getObjectClass(), $names, $mixins);
        if ($eventDispatcher) {
            $descriptor->setEventDispatcher(Container::getInstance()->make($eventDispatcher));
        }
        $attributeFactory->registerObjectListeners($descriptor->dispatcher());
        return $descriptor;
    }

    /**
     * @param $object
     * @return array
     * @throws \Sayla\Objects\Contract\Exception\TransformationError
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
     * @return AttributeFactory
     */
    public function getAttributes(): AttributeFactory
    {
        return $this->attributes;
    }

    /**
     * @return string
     */
    public function getBaseObjectClass(): string
    {
        return $this->baseObjectClass ?? ($this->storeOptions ? StorableTrait::class : DataObject::class);
    }

    /**
     * @return \Sayla\Objects\DataType\DataTypeDescriptor
     */
    public function getDescriptor(): DataTypeDescriptor
    {
        return $this->descriptor;
    }

    public function getInterfaces()
    {
        return $this->interfaces;
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

    /**
     * @return \Sayla\Objects\Contract\Stores\ObjectStore
     */
    public function getStoreStrategy(): ?ObjectStore
    {
        if ($this->storeResolver && $this->storeOptions) {
            $options = $this->storeOptions;
            $options['objectClass'] = $this->getObjectClass();
            return call_user_func($this->storeResolver, $options, $this->name);
        }
        return null;
    }

    /**
     * @return string[]
     */
    public function getTraits()
    {
        return $this->traits;
    }

    public function hasStore()
    {
        return $this->storeOptions !== null;
    }

    /**
     * Hydrate $object with the provided $data.
     *
     * @param iterable $data
     * @return \Sayla\Objects\DataObject
     * @throws \Sayla\Objects\Contract\Exception\HydrationError
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
     * @throws \Sayla\Objects\Contract\Exception\HydrationError
     */
    public function hydrateMany(iterable $results)
    {
        $objectCollection = $this->descriptor->newCollection();
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
     * @throws \Sayla\Objects\Contract\Exception\HydrationError
     */
    public function hydrateObject(DataObject $object, $data)
    {
        try {
            $data = self::convertDataToArray($data);
            $object->init($this->hydrateData($data));
        } catch (Throwable $exception) {
            throw new HydrationError($this->name . ' - ' . $exception->getMessage(), $exception);
        }
        return $object;
    }

    /**
     * @param callable $storeResolver
     */
    public function setStoreResolver(callable $storeResolver): void
    {
        $this->storeResolver = $storeResolver;
    }

    private function getExtractionPipeline(array $attributes = [])
    {
        $pipeline = $this->extractionPipeline
            ?? ($this->extractionPipeline = $this->attributes->getExtractionPipeline());
        return $pipeline->send(new AttributesContext($this->descriptor, $attributes));
    }

    private function getHydrationPipeline(array $attributes = [])
    {
        $pipeline = $this->hydrationPipeline
            ?? ($this->hydrationPipeline = $this->attributes->getHydrationPipeline());
        return $pipeline->send(new AttributesContext($this->descriptor, $attributes));
    }

    /**
     * @return string|null
     */
    public function getStoreDriver(): ?string
    {
        return $this->storeOptions ? $this->storeOptions['driver'] : null;
    }


}