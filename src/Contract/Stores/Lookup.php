<?php

namespace Sayla\Objects\Contract\Stores;

interface Lookup
{
    /**
     * @return \Sayla\Objects\ObjectCollection
     */
    public function all();

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
     * @param $attribute
     * @param $key
     * @return \Sayla\Objects\ObjectCollection
     */
    public function getWhere($attribute, $value);
}