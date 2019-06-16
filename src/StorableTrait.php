<?php

namespace Sayla\Objects;

use Sayla\Objects\Contract\Storable;
use Sayla\Objects\Contract\Stores\ObjectStore;
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
 * @mixin \Sayla\Objects\DataObject
 */
trait StorableTrait
{
    private $storing = false;


    public static function getStore(): ObjectStore
    {
        return DataTypeManager::getInstance()->get(static::dataTypeName())->getStoreStrategy();
    }

    public static function onAfterCreate($listener)
    {
        static::descriptor()->dispatcher()->on(Storable::ON_AFTER_CREATE, $listener);
    }

    public static function onAfterDelete($listener)
    {
        static::descriptor()->dispatcher()->on(Storable::ON_AFTER_DELETE, $listener);
    }

    public static function onAfterUpdate($listener)
    {
        static::descriptor()->dispatcher()->on(Storable::ON_AFTER_UPDATE, $listener);
    }

    public static function onBeforeCreate($listener)
    {
        static::descriptor()->dispatcher()->on(Storable::ON_BEFORE_CREATE, $listener);
    }

    public static function onBeforeDelete($listener)
    {
        static::descriptor()->dispatcher()->on(Storable::ON_BEFORE_DELETE, $listener);
    }

    public static function onBeforeUpdate($listener)
    {
        static::descriptor()->dispatcher()->on(Storable::ON_BEFORE_UPDATE, $listener);
    }

    /**
     * @return $this
     * @throws \Sayla\Exception\Error
     * @throws \Sayla\Objects\Contract\Exception\TriggerError
     */
    public function create()
    {
        if ($this->exists) {
            return $this;
        }
        $instance = $this->getStorable();
        try {
            $instance->storing = true;
            $instance(Storable::ON_BEFORE_SAVE);
            $instance(Storable::ON_BEFORE_CREATE);
            $newAttributes = static::getStore()->create($instance);
            if (is_iterable($newAttributes)) {
                $instance->init($instance->dataType()->hydrateData($newAttributes));
            }
            $instance(Storable::ON_AFTER_CREATE);
            $instance->clearModifiedAttributeFlags();
            $instance->exists = true;
            $instance(Storable::ON_AFTER_SAVE);
        } finally {
            $instance->storing = false;
        }
        return $instance;
    }

    /**
     * @return \Sayla\Objects\StorableTrait
     * @throws \Sayla\Exception\Error
     * @throws \Sayla\Objects\Contract\Exception\TriggerError
     */
    public function delete()
    {
        $instance = $this->getStorable();
        $instance->storing = true;
        try {
            $instance(Storable::ON_BEFORE_DELETE);
            $newAttributes = static::getStore()->delete($instance);
            if (is_iterable($newAttributes)) {
                $instance->init($instance->dataType()->hydrateData($newAttributes));
            }
            $instance(Storable::ON_AFTER_DELETE);
            $instance->clearModifiedAttributeFlags();
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

    protected function getStorable()
    {
        return $this;
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
        if (!$this->exists) {
            $instance = $this->create();
        } else {
            $instance = $this->update();
        }
        return $instance;
    }

    /**
     * @return \Sayla\Objects\StorableTrait
     * @throws \Sayla\Exception\Error
     * @throws \Sayla\Objects\Contract\Exception\TriggerError
     */
    public function update()
    {
        $instance = $this->getStorable();
        try {
            $instance->storing = true;
            $instance(Storable::ON_BEFORE_SAVE);
            $instance(Storable::ON_BEFORE_UPDATE);
            $newAttributes = static::getStore()->update($instance);
            if (is_iterable($newAttributes)) {
                $instance->init($instance->dataType()->hydrateData($newAttributes));
            }
            $instance(Storable::ON_AFTER_UPDATE);
            $instance->clearModifiedAttributeFlags();
            $instance->exists = true;
            $instance(Storable::ON_AFTER_SAVE);
        } finally {
            $instance->storing = false;
        }
        return $instance;
    }
}