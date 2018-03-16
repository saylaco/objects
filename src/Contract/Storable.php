<?php

namespace Sayla\Objects\Contract;

interface Storable extends Keyable
{
    /**
     * @param iterable $attributes
     * @return static
     */
    public static function hydrate(iterable $attributes);

    public function create();

    public function delete();

    public function exists();

    public function setKey($value);

    public function update();
}