<?php

namespace Sayla\Objects\DataType;


use Illuminate\Contracts\Support\Arrayable;
use Sayla\Objects\Builder\Builder;
use Sayla\Objects\Contract\DataType;

class DataTypeManager implements \IteratorAggregate, Arrayable
{
    private static $dataTypeClasses = [];
    private static $instance;
    /** @var callable */
    protected $builderResolver;
    private $aliases = [];
    /** @var \Sayla\Objects\Contract\DataType[] */
    private $dataTypes = [];

    public static function addDataType(string $name, string $class)
    {
        self::$dataTypeClasses[$name] = $class;
    }

    private static function getDataTypeClass(string $name): string
    {
        return self::$dataTypeClasses[$name];
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
        if (!starts_with($builderName, 'build')) {
            throw new \BadMethodCallException(self::class . '::' . $builderName);
        }
        $builderName = lcfirst(str_after($builderName, 'build'));
        $builderExtension = self::getDataTypeClass($builderName);
        $builder = call_user_func_array($builderExtension, $builderArgs);
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
        $name = $this->aliases[$name] ?? $name;

        if (!$this->has($name)) {
            $this->dataTypes[$name] = $this->getBuilder($name)->build();
        }

        return $this->dataTypes[$name];
    }

    public function getBuilder(string $objectClass): Builder
    {
        $builder = new Builder($objectClass);

        if ($this->builderResolver) {
            call_user_func($this->builderResolver, $builder, $objectClass);
        }

        return $builder;
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
     * @param callable $callback
     * @return $this
     */
    public function setBuilderResolver(callable $callback)
    {
        $this->builderResolver = $callback;
        return $this;
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