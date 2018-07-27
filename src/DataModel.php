<?php

namespace Sayla\Objects;

use Sayla\Objects\Contract\Storable;

/**
 * @property callable|int __afterCreate
 * @property callable|int __afterUpdate
 * @property callable|int __afterDelete
 * @property callable|int __afterSave
 * @property callable|int __beforeCreate
 * @property callable|int __beforeUpdate
 * @property callable|int __beforeDelete
 * @property callable|int __beforeSave
 */
abstract class DataModel extends DataObject implements Storable
{
    private $exists = false;
    private $storing = false;

    /**
     * @param \Sayla\Objects\DataModel $dataModel
     */
    protected static function __create($dataModel)
    {
        $dataModel('beforeCreate');
        $newAttributes = $dataModel->dataType()->getStoreStrategy()->create($dataModel);
        if (is_iterable($newAttributes)) {
            $dataModel->init($newAttributes);
        }
        $dataModel('afterCreate');
        $dataModel->clearModifiedAttributeFlags();
    }

    /**
     * @param \Sayla\Objects\DataModel $dataModel
     */
    protected static function __delete($dataModel)
    {
        $dataModel('beforeDelete');
        $newAttributes = $dataModel->dataType()->getStoreStrategy()->delete($dataModel);
        if (is_iterable($newAttributes)) {
            $dataModel->init($newAttributes);
        }
        $dataModel('afterDelete');
        $dataModel->clearModifiedAttributeFlags();
    }

    /**
     * @param \Sayla\Objects\DataModel $dataModel
     */
    protected static function __save($dataModel)
    {
        $dataModel('beforeSave');
        if (!$dataModel->exists) {
            $dataModel('create');
        } else {
            $dataModel('update');
        }
        $dataModel('afterSave');
    }

    /**
     * @param \Sayla\Objects\DataModel $dataModel
     */
    protected static function __update($dataModel)
    {
        $dataModel('beforeUpdate');
        $newAttributes = $dataModel->dataType()->getStoreStrategy()->update($dataModel);
        if (is_iterable($newAttributes)) {
            $dataModel->init($newAttributes);
        }
        $dataModel('afterUpdate');
        $dataModel->clearModifiedAttributeFlags();
    }

    public function create()
    {
        if ($this->exists) {
            return $this;
        }
        $instance = $this->getStoreInstance();
        try {
            $instance->storing = true;
            $instance('create');
            $instance->exists = true;
        } finally {
            $instance->storing = false;
        }
        return $instance;
    }

    public function delete()
    {
        $instance = $this->getStoreInstance();
        $instance->storing = true;
        try {
            $instance('delete');
            $instance->exists = false;
        } finally {
            $instance->storing = false;
        }
        return $instance;
    }

    public function exists()
    {
        return $this->exists;
    }

    public function update()
    {
        $instance = $this->getStoreInstance();
        try {
            $instance->storing = true;
            $instance('update');
            $instance->exists = true;
        } finally {
            $instance->storing = false;
        }
        return $instance;
    }

    protected function getStoreInstance()
    {
        return $this;
    }

    /**
     * @param $attributes
     */
    protected function initialize($attributes): void
    {
        parent::initialize($attributes);
        $this->exists = $this->determineExistence();
    }

    /**
     * @return bool
     */
    protected function isTrackingModifiedAttributes(): bool
    {
        return parent::isTrackingModifiedAttributes() && !$this->isStoring();
    }

    protected function realSerializableProperties(): array
    {
        $properties = parent::realSerializableProperties();
        $properties['exists'] = $this->exists;
        return $properties;
    }

    abstract protected function determineExistence(): bool;

    public function isStoring()
    {
        return $this->storing;
    }

    public function save()
    {
        $instance = $this->getStoreInstance();
        try {
            $instance->storing = true;
            $instance('save');
        } finally {
            $instance->storing = false;
        }
        return $instance;
    }
}