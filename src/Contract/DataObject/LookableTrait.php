<?php

namespace Sayla\Objects\Contract\DataObject;

use Sayla\Objects\Contract\Stores\Lookup;

/**
 * Trait LooksUpDataTrait
 * @method static prepareLookup($lookup)
 */
trait LookableTrait
{
    private static $preparers = [];

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

    protected static function getLookupInstance(): Lookup
    {
        return static::getStore()->lookup();
    }

    /**
     * @return static
     */
    public static function getWhere($attribute, $value)
    {
        return static::lookup()->getWhere($attribute, $value);
    }

    /**
     * @return \Sayla\Objects\Contract\Stores\Lookup
     */
    public static function lookup()
    {
        $lookup = static::getLookupInstance();
        $shouldPrepare = self::$preparers[static::class]
            ?? self::$preparers[static::class] = method_exists(static::class, 'prepareLookup');
        return $shouldPrepare ? static::prepareLookup($lookup) : $lookup;
    }


    protected function determineExistence(): bool
    {
        return filled($this->getKey());
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->getAttributeValue(static::lookup()->getKeyName());
    }

    public function setKey($value)
    {
         $this->setAttributeValue(static::lookup()->getKeyName(), $value);
    }
}