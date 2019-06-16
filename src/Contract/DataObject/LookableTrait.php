<?php

namespace Sayla\Objects\Contract\DataObject;

/**
 * Trait LooksUpDataTrait
 */
trait LookableTrait
{
    /**
     * @return static[]|\Sayla\Objects\ObjectCollection
     */
    public static function all()
    {
        return static::lookup()->all();
    }

    /**
     * @return static
     */
    public static function find($key)
    {
        return static::lookup()->find($key);
    }

    /**
     * @return static
     */
    public static function findBy($attribute, $value)
    {
        return static::lookup()->findBy($attribute, $value);
    }

    /**
     * @return static
     */
    public static function getWhere($attribute, $value)
    {
        return static::lookup()->getWhere($attribute, $value);
    }

    /**
     * @return \Sayla\Objects\Contract\DataObject\Lookable
     */
    public abstract static function lookup();
}