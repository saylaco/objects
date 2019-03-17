<?php

namespace Sayla\Objects\Stores;

trait LooksUpFileRepoTrait
{
    /**
     * @return static[]|\Sayla\Objects\ObjectCollection
     */
    public static function all()
    {
        /** @var \Sayla\Objects\Stores\FileStore\FileRepoStore $store */
        $store = StoreManager::getInstance()->get(static::class);
        return $store->lookup()->all();
    }
    /**
     * @return static
     */
    public static function find($key)
    {
        /** @var \Sayla\Objects\Stores\FileStore\FileRepoStore $store */
        $store = StoreManager::getInstance()->get(static::class);
        return $store->lookup()->find($key);
    }
    /**
     * @return static
     */
    public static function findBy($attribute,$key)
    {
        /** @var \Sayla\Objects\Stores\FileStore\FileRepoStore $store */
        $store = StoreManager::getInstance()->get(static::class);
        return $store->lookup()->findBy($attribute,$key);
    }

}