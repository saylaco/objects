<?php

namespace Sayla\Objects\DataType;


use Illuminate\Contracts\Support\Arrayable;
use Sayla\Objects\Builder\Builder;
use Sayla\Objects\Contract\DataType;
use Sayla\Objects\DataObject;

class DataTypeManager implements \IteratorAggregate, Arrayable
{
    private static $builders = [];
    private static $instance;
    private $aliases = [];
    /** @var \Sayla\Objects\Contract\DataType[] */
    private $dataTypes = [];

    public static function addBuilderExtension(string $builderName, callable $callback)
    {
        self::$builders[$builderName] = $callback;
    }

    private static function getBuilderExtension(string $builderName): callable
    {
        return self::$builders[$builderName];
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
        $builder->onPostBuild(function (DataType $dataType) {
            $this->add($dataType);
        });
        return $builder;
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

    public function get(string $name): DataType
    {
        if (!$this->has($name) && is_subclass_of($name, DataObject::class)) {
            $this->dataTypes[$name] = $this->getBuilder($name)->build();
        }
        $name = $this->aliases[$name] ?? $name;

        return $this->dataTypes[$name];
    }

    public function getBuilder(string $objectClass): Builder
    {
        return new Builder($objectClass);
    }

    public function getDescriptor(string $name): DataTypeDescriptor
    {
        return clone $this->get($name)->getDescriptor();
    }

    /**
     * @return \ArrayIterator|\Sayla\Objects\Contract\DataType
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->dataTypes);
    }

    public function has(string $name): bool
    {
        return isset($this->dataTypes[$name]) || isset($this->aliases[$name]);
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