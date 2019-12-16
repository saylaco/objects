<?php

namespace Sayla\Objects\Contract\Stores;

interface Lookup
{
    /**
     * @return \Sayla\Objects\ObjectCollection
     */
    public function all();

    public function exists(string $key): bool;

    /**
     * @param $key
     * @return \Sayla\Objects\Contract\IDataObject
     */
    public function find($key);

    /**
     * @param $attribute
     * @param $key
     * @return \Sayla\Objects\Contract\IDataObject
     */
    public function findBy($attribute, $value);

    /**
     * @return string
     */
    public function getKeyName(): string;

    /**
     * @param $attribute
     * @param $key
     * @return \Sayla\Objects\ObjectCollection
     */
    public function getWhere($attribute, $value);
}