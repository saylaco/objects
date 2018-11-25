<?php

namespace Sayla\Objects\Attribute;

use Sayla\Objects\Attribute\Property\PropertySet;
use Sayla\Objects\Contract\Property;

class Attribute extends PropertySet
{

    /**
     * @param string $propertyType
     * @return \Sayla\Objects\Attribute\Attribute
     */
    public function filterByPropertyType(string $propertyType)
    {
        $properties = [];
        foreach ($this as $property) {
            if ($property->getTypeHandle() == $propertyType) {
                $properties[] = $property;
            }
        }
        return new Attribute($this->getTypeHandle(), $this->getName(), $properties);
    }

    public function getFirst(): ?Property
    {
        return array_first($this->toArray());
    }

    /**
     * @return \Sayla\Objects\Attribute\Property\Property[]
     */
    public function getIterator()
    {
        return parent::getIterator();
    }

    public function hasPropertyOfType(string $propertyType)
    {
        foreach ($this as $property) {
            if ($property->getTypeHandle() == $propertyType) {
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