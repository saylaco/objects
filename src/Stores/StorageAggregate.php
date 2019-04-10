<?php

namespace Sayla\Objects\Stores;

use Sayla\Objects\Contract\ObjectStore;
use Sayla\Objects\Contract\Storable;

class StorageAggregate implements ObjectStore
{
    /** @var  ObjectStore[] */
    protected $strategies;

    public function addStrategy(ObjectStore $strategy)
    {
        $this->strategies[] = $strategy;
        return $this;

    }

    /**
     * @param Storable $object
     */
    public function create(Storable $object)
    {
        foreach ($this->strategies as $strategy)
            $strategy->create($object);
    }

    /**
     * @param Storable $object
     */
    public function delete(Storable $object)
    {
        foreach ($this->strategies as $strategy)
            $strategy->delete($object);
    }

    public function toStoreString($name, $arguments): string
    {
        return 'Aggregate(' . join(',', $this->strategies) . ')';
    }

    /**
     * @param Storable $object
     */
    public function update(Storable $object)
    {
        foreach ($this->strategies as $strategy)
            $strategy->update($object);
    }
}