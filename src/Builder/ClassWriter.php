<?php

namespace Sayla\Objects\Builder;

use Sayla\Objects\Attribute\PropertyTypeSet;
use Sayla\Objects\Contract\DataType;
use Sayla\Objects\Contract\PropertyType;
use Sayla\Objects\DataType\StandardDataType;
use Sayla\Objects\DataType\StoringDataType;
use Sayla\Objects\Transformers\ValueTransformerFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Builder
{
    /** @var callable[] */
    protected $buildCallbacks = [];
    /** @var callable */
    protected $optionsCallback;
    protected $options = [];
    protected $store = null;
    /** @var PropertyType[] */
    protected $propertyTypes = [];
    /** @var string */
    private $dataTypeClass;

    /**
     * DataTypeBuilder constructor.
     * @param string $objectClass
     */
    public function __construct(string $objectClass)
    {
        $this->options['objectClass'] = $objectClass;
    }

    /**
     * @param string $optionName
     * @param $optionValue
     * @return $this
     */
    public function __call(string $optionName, $optionValues)
    {
        $this->options[$optionName] = $optionValues[0] ?? null;
        return $this;
    }

    public function addPropertyType(PropertyType $propertyType)
    {
        $this->propertyTypes[$propertyType::getHandle()] = $propertyType;
        return $this;
    }

    /**
     * @param array $attributeDefinitions
     * @return $this
     */
    public function attributeDefinitions(array $attributeDefinitions)
    {
        $this->options[__FUNCTION__] = $attributeDefinitions;
        return $this;
    }

    /**
     * @return \Sayla\Objects\Contract\DataType
     */
    public function build()
    {
        $dataType = self::makeDataType($this->getDataTypeClass(), $this->getOptions());
        if (filled($this->buildCallbacks)) {
            $callbacks = $this->buildCallbacks;
            $postBuild = array_pull($callbacks, 'post');
            foreach ($this->buildCallbacks as $buildCallback)
                call_user_func($buildCallback, $dataType);
            if ($postBuild) {
                call_user_func($postBuild, $dataType);
            }
        }
        return $dataType;
    }

    /**
     * @param string|DataType $dataTypeClass
     * @param array $options
     * @return \Sayla\Objects\Contract\DataType
     */
    public static function makeDataType(string $dataTypeClass, array $options): DataType
    {
        return forward_static_call([$dataTypeClass, 'build'], $options);
    }

    public function getOptions(): array
    {
        $options = $this->getOptionResolver()->resolve($this->options);
        foreach ($this->propertyTypes as $propertyType)
            $options['propertyTypes']->push($propertyType);
        if (isset($this->optionsCallback)) {
            $options = call_user_func($this->optionsCallback, $options) ?? $options;
        }
        return $options;
    }

    private function getOptionResolver()
    {
        if (!isset($this->optionsResolver)) {
            $resolver = new OptionsResolver();
            forward_static_call($this->getDataTypeClass() . '::configureOptions', $resolver);
            $this->optionsResolver = $resolver;
        }
        return $this->optionsResolver;
    }

    public function name(string $name)
    {
        $this->options[__FUNCTION__] = $name;
        return $this;
    }

    /**
     * @param \Sayla\Objects\ObjectDispatcher $objectDispatcher
     * @return $this
     */
    public function objectDispatcher(\Sayla\Objects\ObjectDispatcher $objectDispatcher)
    {
        $this->options[__FUNCTION__] = $objectDispatcher;
        return $this;
    }

    public function onOptionsResolution(callable $callback)
    {
        $this->optionsCallback = $callback;
        return $this;
    }

    /**
     * @param \Sayla\Objects\Contract\PropertyType[]|PropertyTypeSet $propertyTypes
     * @return $this
     */
    public function propertyTypes(PropertyTypeSet $propertyTypes)
    {
        $this->options[__FUNCTION__] = $propertyTypes;
        return $this;
    }

    public function store(string $driver, array $options = [], string $storeName = null)
    {
        $storeName = $storeName ?? $this->options['objectClass'];
        if (!isset($this->dataTypeClass)) {
            $this->setDataTypeClass(StoringDataType::class);
            $this->options['storeName'] = $this->store['name'];
        }
        $this->store = [
            'name' => $storeName,
            'options' => $options,
            'driver' => $driver,
        ];
        return $this;
    }

    /**
     * @param string $dataTypeClass
     */
    public function setDataTypeClass(string $dataTypeClass)
    {
        $this->dataTypeClass = $dataTypeClass;
        return $this;
    }

    /**
     * @param \Sayla\Objects\Transformers\ValueTransformerFactory $valueFactory
     * @return $this
     */
    public function valueFactory(ValueTransformerFactory $valueFactory)
    {
        $this->options[__FUNCTION__] = $valueFactory;
        return $this;
    }

    /**
     * @return string
     */
    public function getDataTypeClass(): string
    {
        return $this->dataTypeClass ?? StandardDataType::class;
    }

    /**
     * @return array
     */
    public function getStoreOptions(): ?array
    {
        return $this->store;
    }
}