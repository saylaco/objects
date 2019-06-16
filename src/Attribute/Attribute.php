<?php

namespace Sayla\Objects\Attribute;

use Sayla\Objects\Contract\Attributes\Property;

class Attribute extends PropertySet
{
    /** @var string */
    private $typeHandle;

    /**
     * Property constructor.
     * @param mixed $value
     * @param string $typeHandle
     * @param string $name
     */
    {
        $this->typeHandle = $typeHandle;
        parent::__construct($name, $value);
    }

    /**
     * @param string $propertyType
     * @return \Sayla\Objects\Attribute\Attribute
     */
    public function filterByProperty(string $propertyName)
    {
        $properties = [];
        foreach ($this->getValue() as $property) {
            if ($property->getName() == $propertyName) {
                $properties[] = $property;
            }
        }
        return new Attribute($this->typeHandle, $this->getName(), $properties);
    }

    public function getFirst(): ?Property
    {
        return array_first($this->toArray());
    }

    /**
     * @return \Sayla\Objects\Attribute\Property[]
     */
    public function getIterator()
    {
        return parent::getIterator();
    }

    public function getTypeHandle(): string
    {
        return $this->typeHandle;
    }

    public function hasProperty(string $propertyType)
    {
        foreach ($this as $property) {
            if ($property->getName() == $propertyType) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function toCollection()
    {
        return collect($this->toArray());
    }
}