<?php

namespace Sayla\Objects\Stores;

use Sayla\Objects\Contract\ObjectStore;
use Sayla\Objects\DataModel;

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
     * @param \Sayla\Objects\DataModel $object
     */
    public function create(DataModel $object)
    {
        foreach ($this->strategies as $strategy)
            $strategy->create($object);
    }

    /**
     * @param \Sayla\Objects\DataModel $object
     */
    public function delete(DataModel $object)
    {
        foreach ($this->strategies as $strategy)
            $strategy->delete($object);
    }

    public function toStoreString($name, $arguments): string
    {
        return 'Aggregate(' . join(',', $this->strategies) . ')';
    }

    /**
     * @param \Sayla\Objects\DataModel $object
     */
    public function update(DataModel $object)
    {
        foreach ($this->strategies as $strategy)
            $strategy->update($object);
    }
}