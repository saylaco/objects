<?php

namespace Sayla\Objects;

use Sayla\Objects\Contract\StoreStrategy;
use Sayla\Objects\Pipes\NullPipeManager;
use Sayla\Objects\Pipes\PipeManager;

class ObjectStore
{
    /** @var \Sayla\Objects\Inspection\ObjectDescriptor */
    protected $descriptor;
    /** @var  StoreStrategy */
    protected $strategy;
    /** @var  \Sayla\Objects\Pipes\ObjectPipeManager */
    protected $observer;

    /**
     * ObjectStore constructor.
     * @param \Sayla\Objects\Contract\StoreStrategy $strategy
     */
    public function __construct(StoreStrategy $strategy)
    {
        $this->strategy = $strategy;
        $this->observer = new NullPipeManager();
    }

    /**
     * @param \Sayla\Objects\BaseDataModel $object
     */
    public function create(BaseDataModel $object)
    {
        $object('beforeCreate');
        $this->observer->run($object, 'create', function (BaseDataModel $object) {
            $this->performCreate($object);
            return $object;
        }, 'onObjectCreate');
        $object('afterCreate');
    }

    /**
     * @param Object|\Sayla\Objects\BaseDataModel $object
     */
    public function performCreate(BaseDataModel $object)
    {
        $newAttributes = $this->getStrategy()->create($object);
        if (is_iterable($newAttributes)) {
            $object->initStoreData($newAttributes);
        }
    }

    public function getStrategy(): StoreStrategy
    {
        return $this->strategy;
    }

    /**
     * @param \Sayla\Objects\BaseDataModel $object
     */
    public function delete(BaseDataModel $object)
    {
        $object('beforeDelete');
        $this->observer->run($object, 'delete', function (BaseDataModel $object) {
            $this->performDelete($object);
            return $object;
        }, 'onObjectDelete');
        $object('afterDelete');
    }

    /**
     * @param \Sayla\Objects\BaseDataModel $object
     */
    public function performDelete(BaseDataModel $object)
    {
        $newAttributes = $this->getStrategy()->delete($object);
        if (is_iterable($newAttributes)) {
            $object->initStoreData($newAttributes);
        }
    }


    public function setObserver(PipeManager $observer)
    {
        $this->observer = $observer;
    }

    /**
     * @param \Sayla\Objects\BaseDataModel $object
     */
    public function update(BaseDataModel $object)
    {
        $object('beforeUpdate');
        $this->observer->run($object, 'update', function (BaseDataModel $object) {
            $this->performUpdate($object);
            return $object;
        }, 'onObjectUpdate');
        $object('afterUpdate');
    }

    /**
     * @param \Sayla\Objects\BaseDataModel $object
     */
    public function performUpdate(BaseDataModel $object)
    {
        $newAttributes = $this->getStrategy()->update($object);
        if (is_iterable($newAttributes)) {
            $object->initStoreData($newAttributes);
        }
    }

}