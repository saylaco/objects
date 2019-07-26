<?php

namespace Sayla\Objects\DataType;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use Sayla\Objects\Attribute\AttributeFactory;
use Sayla\Objects\Contract\DataObject\StorableObjectTrait;
use Sayla\Objects\Contract\DataType\ObjectResponseFactory as IObjectResponseFactory;
use Sayla\Objects\Contract\Exception\HydrationError;
use Sayla\Objects\Contract\Stores\ObjectStore;
use Sayla\Objects\DataObject;
use Sayla\Objects\ObjectCollection;
use Sayla\Objects\ObjectDispatcher;
use Sayla\Objects\SimpleEventDispatcher;
use Throwable;

final class DataType
{
    /** @var AttributeFactory */
    protected $attributes;
    /**
     * @var string
     */
    protected $baseObjectClass;
    /** @var \Illuminate\Contracts\Events\Dispatcher */
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
    /** @var string|ObjectCollection $objectCollectionClass */
    private $objectCollectionClass;
    private $resolveOnRequest;
    /** @var IObjectResponseFactory */
    private $responseFactory;
    /** @var callable */
    private $storeResolver;
    private $traits;

    public function __construct(array $options)
    {
        $this->objectClass = $options['objectClass'];
        $this->name = $options['name'];
        $this->storeOptions = $options['store'] ?? null;
        $this->traits = $options['traits'];
        $this->interfaces = $options['interfaces'];
        $this->eventDispatcher = $options['dispatcher'] ?? null;
        $this->objectCollectionClass = $options['collectionClass'] ?? null;
        $this->resolveOnRequest = $options['resolveOnRequest'] ?? [];
        $this->attributes = self::makeAttributeFactory($options);
        $this->descriptor = self::makeDescriptor($this->attributes, $options['name']);
        $this->attributes->registerObjectListeners($this->dispatcher());
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
        $atFact = new AttributeFactory($options['objectClass'], $options['attributes'], $options['classFile']);
        if ($options['propertyTypeOptions']) {
            $atFact->setPropertyTypeOptions($options['propertyTypeOptions']);
        }
        return $atFact;
    }

    public static function makeDescriptor(AttributeFactory $attributeFactory, string $name): DataTypeDescriptor
    {
        $names = $attributeFactory->getNames();
        $mixins = $attributeFactory->getMixins();
        return new DataTypeDescriptor($name, $attributeFactory->getObjectClass(), $names, $mixins);
    }

    /**
     * @return \Sayla\Objects\ObjectDispatcher
     */
    public function dispatcher(): ObjectDispatcher
    {
        return new ObjectDispatcher($this->getEventDispatcher(), $this->name);
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
        return $this->baseObjectClass ?? ($this->storeOptions ? StorableObjectTrait::class : DataObject::class);
    }

    /**
     * @return \Sayla\Objects\DataType\DataTypeDescriptor
     */
    public function getDescriptor(): DataTypeDescriptor
    {
        return $this->descriptor;
    }

    protected function getEventDispatcher(): Dispatcher
    {
        return $this->eventDispatcher ?? ($this->eventDispatcher = new SimpleEventDispatcher());
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

    public function getResponseFactory(): IObjectResponseFactory
    {
        if (!$this->responseFactory) {
            return new ObjectResponseFactory($this->resolveOnRequest);
        }
        return $this->responseFactory;
    }

    /**
     * @param \Sayla\Objects\Contract\DataType\ObjectResponseFactory $responseFactory
     * @return DataType
     */
    public function setResponseFactory(IObjectResponseFactory $responseFactory): DataType
    {
        $this->responseFactory = $responseFactory;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getStoreDriver(): ?string
    {
        return $this->storeOptions ? $this->storeOptions['driver'] : null;
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

    public function hasDispatcher(): bool
    {
        return !empty($this->eventDispatcher);
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

    public function isAttribute(string $name)
    {
        return $this->getAttributes()->isAttribute($name);
    }

    /**
     * @return \Sayla\Objects\ObjectCollection
     */
    public function newCollection(): ObjectCollection
    {
        if ($this->objectCollectionClass) {
            return $this->objectCollectionClass::make();
        }
        return ObjectCollection::makeFor($this->name);
    }

    public function setDispatcher(Dispatcher $dispatcher)
    {
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * @param \Sayla\Objects\ObjectCollection|string $objectCollectionClass
     */
    public function setObjectCollectionClass($objectCollectionClass): void
    {
        $this->objectCollectionClass = $objectCollectionClass;
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


}