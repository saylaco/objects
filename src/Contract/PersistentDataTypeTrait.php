<?php

namespace Sayla\Objects\Contract;

trait PersistentDataTypeTrait
{

    /**
     * @var \Sayla\Objects\Contract\ObjectStore
     */
    protected $storeStrategy;

    /**
     * @return \Sayla\Objects\Contract\ObjectStore
     */
    public function getStoreStrategy(): \Sayla\Objects\Contract\ObjectStore
    {
        return $this->storeStrategy;
    }

    public function onCreate($listener)
    {
        $this->getObjectDispatcher()->on('create', $listener);
        return $this;
    }


    public function onDelete($listener)
    {
        $this->getObjectDispatcher()->on('delete', $listener);
        return $this;
    }

    public function onUpdate($listener)
    {
        $this->getObjectDispatcher()->on('update', $listener);
        return $this;
    }
}