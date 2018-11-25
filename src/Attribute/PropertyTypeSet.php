<?php

namespace Sayla\Objects\Attribute;

use Sayla\Helper\Data\BaseHashMap;
use Sayla\Objects\Contract\PropertyType;

class PropertyTypeSet extends BaseHashMap
{
    public function __construct($properties = null)
    {
        if ($properties) {
            $this->fill($properties);
        }
    }

    /**
     * @param string $property
     * @return \Sayla\Objects\Contract\PropertyType
     */
    public function get(string $name): PropertyType
    {
        return $this->items[$name];
    }

    /**
     * @return \ArrayIterator|\Traversable|PropertyType[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    public function push(PropertyType $property)
    {
        $this->items[$property->getName()] = $property;
        return $this;
    }

    public function put($name = null, PropertyType $property)
    {
        $name = is_int($name) ? $property->getName() : $name;
        $this->items[$name] = $property;
        return $this;
    }
}