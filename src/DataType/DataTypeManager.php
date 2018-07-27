<?php

namespace Sayla\Objects\DataType;


use Illuminate\Contracts\Support\Arrayable;
use Sayla\Objects\Builder\Builder;
use Sayla\Objects\Contract\DataType;
use Sayla\Objects\Contract\ObjectStore;
use Sayla\Objects\DataObject;

class DataTypeManager implements \IteratorAggregate, Arrayable
{
    private static $instance;
    private static $builders = [];
    /** @var \Sayla\Objects\Contract\DataType[] */
    private $dataTypes = [];
    private $aliases = [];

    public static function addBuilderExtension(string $builderName, callable $callback)
    {
        self::$builders[$builderName] = $callback;
    }

    public static function getInstance(): self
    {
        return self::$instance ?? (self::$instance = new self());
    }

    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }

    public function __call(string $builderName, array $builderArgs): Builder
    {
        if (!starts_with($builderName, 'add')) {
            throw new \BadMethodCallException(self::class . '::' . $builderName);
        }
        $builderName = lcfirst(str_after($builderName, 'add'));
        $builderExtension = self::getBuilderExtension($builderName);
        $builder = call_user_func_array($builderExtension, $builderArgs);
        $builder->onBuild(function (DataType $dataType) {
            $this->add($dataType);
        });
        return $builder;
    }

    private static function getBuilderExtension(string $builderName): callable
    {
        return self::$builders[$builderName];
    }

    public function add(DataType $dataType)
    {
        /** @var \Sayla\Objects\DataObject|string $objectClass */
        $objectClass = $dataType->getObjectClass();
        $objectClass::setDataTypeManager($this);
        if (!isset($this->aliases[$objectClass])) {
            $this->aliases[$objectClass] = $dataType->getName();
        }
        $this->dataTypes[$dataType->getName()] = $dataType;
        return $this;
    }

    public function getDescriptor(string $name): DataTypeDescriptor
    {
        return clone $this->get($name)->getDescriptor();
    }

    public function get(string $name): DataType
    {
        if (!$this->has($name) && is_subclass_of($name, DataObject::class)) {
            $this->getBuilder($name)->build();
        }
        $name = $this->aliases[$name] ?? $name;

        return $this->dataTypes[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->dataTypes[$name]) || isset($this->aliases[$name]);
    }

    public function getBuilder(string $objectClass): Builder
    {
        $builder = new Builder($objectClass);
        $builder->onBuild(function (DataType $dataType) {
            $this->add($dataType);
        });
        return $builder;

    }

    /**
     * @return \ArrayIterator|\Sayla\Objects\Contract\DataType
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->dataTypes);
    }

    /**
     * @param string $objectClass
     * @param \Sayla\Objects\Contract\ObjectStore $storeStrategy
     * @return \Sayla\Objects\Builder\Builder
     */
    public function getStorableBuilder(string $objectClass,
                                       ObjectStore $storeStrategy = null): Builder
    {
        $builder = new Builder($objectClass);
        $builder
            ->setDataTypeClass(StoringDataType::class)
            ->onBuild(function (DataType $dataType) {
                $this->add($dataType);
            });
        if ($storeStrategy) {
            $builder->storeStrategy($storeStrategy);
        }
        return $builder;

    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->dataTypes;
    }
}