<?php

namespace Sayla\Objects;

use Sayla\Objects\Contract\ObjectStore;
use Sayla\Objects\DataType\DataTypeManager;

/**
 * @property callable|int __afterCreate
 * @property callable|int __afterUpdate
 * @property callable|int __afterDelete
 * @property callable|int __afterSave
 * @property callable|int __beforeCreate
 * @property callable|int __beforeUpdate
 * @property callable|int __beforeDelete
 * @property callable|int __beforeSave
 * @mixin \Sayla\Objects\Contract\Storable
 * @mixin \Sayla\Objects\DataObject
 */
trait StorableTrait
{
    private $exists = false;
    private $storing = false;

    /**
     * @param static $dataModel
     */
    protected static function __create($dataModel)
    {
        $dataModel('beforeCreate');
        $newAttributes = static::getStore()->create($dataModel);
        if (is_iterable($newAttributes)) {
            $dataModel->init($dataModel->dataType()->hydrateData($newAttributes));
        }
        $dataModel('afterCreate');
        $dataModel->clearModifiedAttributeFlags();
    }

    /**
     * @param static $dataModel
     */
    protected static function __delete($dataModel)
    {
        $dataModel('beforeDelete');
        $newAttributes = static::getStore()->delete($dataModel);
        if (is_iterable($newAttributes)) {
            $dataModel->init($dataModel->dataType()->hydrateData($newAttributes));
        }
        $dataModel('afterDelete');
        $dataModel->clearModifiedAttributeFlags();
    }

    /**
     * @param static $dataModel
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
     * @param \Sayla\Objects\StorableTrait $dataModel
     */
    protected static function __update($dataModel)
    {
        $dataModel('beforeUpdate');
        $newAttributes = static::getStore()->update($dataModel);
        if (is_iterable($newAttributes)) {
            $dataModel->init($dataModel->dataType()->hydrateData($newAttributes));
        }
        $dataModel('afterUpdate');
        $dataModel->clearModifiedAttributeFlags();
    }

    public static function getStore(): ObjectStore
    {
        return DataTypeManager::getInstance()->get(static::dataTypeName())->getStoreStrategy();
    }

    public static function onCreate($listener)
    {
        static::descriptor()->dispatcher()->on('create', $listener);
    }

    public static function onDelete($listener)
    {
        static::descriptor()->dispatcher()->on('delete', $listener);
    }

    public static function onUpdate($listener)
    {
        static::descriptor()->dispatcher()->on('update', $listener);
    }

    public function create()
    {
        if ($this->exists) {
            return $this;
        }
        $instance = $this->getStorable();
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
        $instance = $this->getStorable();
        $instance->storing = true;
        try {
            $instance('delete');
            $instance->exists = false;
        } finally {
            $instance->storing = false;
        }
        return $instance;
    }

    abstract protected function determineExistence(): bool;

    public function exists()
    {
        return $this->exists;
    }

    protected function getStorable()
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

    public function isStoring()
    {
        return $this->storing;
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

    public function save()
    {
        $instance = $this->getStorable();
        try {
            $instance->storing = true;
            $instance('save');
        } finally {
            $instance->storing = false;
        }
        return $instance;
    }

    public function update()
    {
        $instance = $this->getStorable();
        try {
            $instance->storing = true;
            $instance('update');
            $instance->exists = true;
        } finally {
            $instance->storing = false;
        }
        return $instance;
    }
}