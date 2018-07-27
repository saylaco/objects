<?php

namespace Sayla\Objects;

use Sayla\Exception\Error;
use Sayla\Helper\Data\BaseArrayObject;

abstract class Set extends BaseArrayObject
{
    protected $allowUndefinedKeys = false;

    protected function fill(iterable $items)
    {
        foreach ($items as $i => $item) {
            $this[$i] = $item;
        }
        return $this;
    }

    public function hasKey(string $propertyName): bool
    {
        return isset($this->items[$propertyName]);
    }

    public function offsetUnset($offset)
    {
        throw new Error('Removal of item is not supported');
    }
}