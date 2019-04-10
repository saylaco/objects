<?php

namespace Sayla\Objects\Stores\FileStore;

use Sayla\Objects\Stores\FileStore;

/**
 * Trait LooksUpFileRepoTrait
 * @mixin \Sayla\Objects\StorableTrait
 * @method static FileStore\FileRepoStore getStore()
 */
trait LooksUpFileRepoTrait
{
    /**
     * @return static[]|\Sayla\Objects\ObjectCollection
     */
    public static function all()
    {
        return static::getStore()->lookup()->all();
    }

    /**
     * @return static
     */
    public static function find($key)
    {
        return static::getStore()->lookup()->find($key);
    }

    /**
     * @return static
     */
    public static function findBy($attribute, $key)
    {
        return static::getStore()->lookup()->findBy($attribute, $key);
    }

}