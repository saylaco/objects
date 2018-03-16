<?php

namespace Sayla\Objects\Contract;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

interface Attributable extends Jsonable, Arrayable, \JsonSerializable, \ArrayAccess, \IteratorAggregate
{
    /**
     * Populate with an array of attributes.
     *
     * @param iterable $attributes
     * @return self
     */
    public function fill(iterable $attributes);

    /**
     * @return \ArrayIterator
     */
    public function getIterator();

    /**
     * @param string $attributeName
     * @return bool
     */
    public function isAttributeSet(string $attributeName): bool;

    public function offsetExists($offset);

    public function offsetGet($offset);

    public function offsetSet($offset, $value);

    public function offsetUnset($offset);

    public function pluck(...$attributeNames);

    public function toArray(): iterable;
}