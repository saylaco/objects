<?php

namespace Sayla\Objects\Attribute;

use Sayla\Objects\Contract\PropertyType;
use Sayla\Objects\Set;

/**
 * @method getIterator() PropertyType[]
 */
class PropertyTypeSet extends Set
{
    protected $allowUndefinedKeys = true;

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