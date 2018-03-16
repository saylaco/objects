<?php

namespace Sayla\Objects;

use Sayla\Util\Factory\Factory;

class ObjectFactory extends Factory
{
    /** @var  string */
    protected $defaultObjectClass;
    /** @var  string */
    protected $property;

    /**
     * ObjectFactory constructor.
     * @param string $property
     */
    public function __construct($property = null)
    {
        if ($property !== null) {
            $this->setResolverProperty($property);
        }
    }

    /**
     * @param string $property
     * @return $this
     */
    public function setResolverProperty(string $property)
    {
        $this->property = $property;
        return $this;
    }

    public function addNamedResolver(string $name, \Closure $resolver)
    {
        $this->addResolver($resolver, $name);
    }

    public function hydrate($attributes = [])
    {
        if (isset($this->property) && isset($attributes[$this->property])) {
            $type = $attributes[$this->property];
            $factory = $this->requireResolver($type);
            return $this->resolve($factory, $attributes);
        }
        if (isset($this->defaultObjectClass)) {
            return DataObject::hydrateObject($this->defaultObjectClass, $attributes);
        }
        throw new \ErrorException('Could not determine instance type - ' . json_encode($attributes));
    }

    /**
     * @param string $defaultObjectClass
     * @return $this
     */
    public function setDefaultObjectClass(string $defaultObjectClass)
    {
        $this->defaultObjectClass = $defaultObjectClass;
        return $this;
    }

}