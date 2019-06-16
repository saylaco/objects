<?php

namespace Sayla\Objects\Stores\FileStore;

use Sayla\Objects\Contract\DataObject\LookableTrait;
use Sayla\Objects\Stores\FileStore;

/**
 * Trait LooksUpFileRepoTrait
 * @mixin \Sayla\Objects\StorableTrait
 * @method static FileStore\FileDataStore getStore()
 */
trait LooksUpFileRepoTrait
{
    use LookableTrait;

    /**
     * @return \Sayla\Objects\Stores\FileStore\ObjectCollectionLookup
     */
    public static function lookup()
    {
        return static::getStore()->lookup();
    }

    protected function determineExistence(): bool
    {
        return filled($this->getKey()) && static::getStore()->exists($this->getKey());
    }

    public function getKey()
    {
        return $this[static::getStore()->getPrimaryKey()];
    }
}