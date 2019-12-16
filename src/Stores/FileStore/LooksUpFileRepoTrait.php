<?php

namespace Sayla\Objects\Stores\FileStore;

use Sayla\Objects\Contract\DataObject\LookableTrait;
use Sayla\Objects\Stores\FileStore;

/**
 * Trait LooksUpFileRepoTrait
 * @mixin \Sayla\Objects\Contract\DataObject\StorableObjectTrait
 * @method static FileStore\FileDataStore getStore()
 * @method static ObjectCollectionLookup lookup
 */
trait LooksUpFileRepoTrait
{
    use LookableTrait;

    protected function determineExistence(): bool
    {
        return filled($this->getKey()) && static::lookup()->exists($this->getKey());
    }
}