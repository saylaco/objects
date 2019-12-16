<?php

namespace Sayla\Objects\Stores;

use Sayla\Objects\Contract\Storable;
use Sayla\Objects\Contract\Stores\ObjectStore;

class LazyObjectStore implements ObjectStore
{
    /** @var  ObjectStore[] */
    protected $strategies;
    /** @var callable */
    private $strategyPicker;

    /**
     * StorageAggregate constructor.
     * @param callable $strategyPicker
     */
    public function __construct(callable $strategyPicker)
    {
        $this->strategyPicker = $strategyPicker;
    }

    public function addStrategy(ObjectStore $strategy)
    {
        $this->strategies[] = $strategy;
        return $this;

    }

    /**
     * @param Storable $object
     * @return iterable
     */
    public function create(Storable $object)
    {
        return $this->getStrategy($object)->create($object);
    }

    /**
     * @param Storable $object
     */
    public function delete(Storable $object)
    {
        return $this->getStrategy($object)->delete($object);
    }

    public function exists($key)
    {
        foreach ($this->strategies as $strategy)
            if ($strategy->exists($key)) {
                return true;
            }
        return false;
    }

    protected function getStrategy(Storable $object): ObjectStore
    {
        return call_user_func($this->strategyPicker, $object);
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
        return $this->getStrategy($object)->update($object);
    }
}