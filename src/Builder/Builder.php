<?php

namespace Sayla\Objects\Builder;

use Sayla\Objects\Attribute\PropertyTypeSet;
use Sayla\Objects\Contract\DataType;
use Sayla\Objects\Contract\ObjectStore;
use Sayla\Objects\Contract\PropertyType;
use Sayla\Objects\DataType\StandardDataType;
use Sayla\Objects\DataType\StoringDataType;
use Sayla\Objects\Stores\StoreManager;
use Sayla\Objects\Transformers\ValueTransformerFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Builder
{
    /** @var callable[] */
    protected $buildCallbacks = [];
    /** @var callable */
    protected $optionsCallback;
    protected $options = [];
    /** @var PropertyType[] */
    protected $propertyTypes = [];
    private $resolvedOptions;
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
        $dataType = $this->makeDataType();
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
     * @return mixed
     */
    protected function makeDataType(): DataType
    {
        if (!isset($this->resolvedOptions)) {
            $this->resolvedOptions = $this->getOptions();
        }
        $dataType = forward_static_call($this->getDataTypeClass() . '::build', $this->resolvedOptions);
        return $dataType;
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

    public function onBuild(callable $callback)
    {
        $this->buildCallbacks[] = $callback;
        return $this;
    }

    public function onPostBuild(callable $callback)
    {
        $this->buildCallbacks['post'] = $callback;
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

    public function storeDriver(string $driver, array $options = [])
    {
        if (!isset($this->dataTypeClass)) {
            $this->setDataTypeClass(StoringDataType::class);
        }
        if (!isset($this->options['storeName'])) {
            $this->options['storeName'] = $this->options['objectClass'];
        }
        StoreManager::getInstance()->addStore($this->options['storeName'], $driver, $options);
        return $this;
    }

    public function storeStrategy(ObjectStore $value)
    {
        if (!isset($this->dataTypeClass)) {
            $this->setDataTypeClass(StoringDataType::class);
        }
        $this->options[__FUNCTION__] = $value;
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

}