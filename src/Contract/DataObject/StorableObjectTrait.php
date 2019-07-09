<?php

namespace Sayla\Objects\Contract\DataObject;

use Illuminate\Support\Arr;
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
trait StorableObjectTrait
{
    private $storing = false;


    public static function getStore(): ObjectStore
    {
        return DataTypeManager::resolve()->get(static::dataTypeName())->getStoreStrategy();
    }

    public static function onAfterCreate($listener)
    {
        static::dataType()->dispatcher()->on(Storable::ON_AFTER_CREATE, $listener);
    }

    public static function onAfterDelete($listener)
    {
        static::dataType()->dispatcher()->on(Storable::ON_AFTER_DELETE, $listener);
    }

    public static function onAfterUpdate($listener)
    {
        static::dataType()->dispatcher()->on(Storable::ON_AFTER_UPDATE, $listener);
    }

    public static function onBeforeCreate($listener)
    {
        static::dataType()->dispatcher()->on(Storable::ON_BEFORE_CREATE, $listener);
    }

    public static function onBeforeDelete($listener)
    {
        static::dataType()->dispatcher()->on(Storable::ON_BEFORE_DELETE, $listener);
    }

    public static function onBeforeUpdate($listener)
    {
        static::dataType()->dispatcher()->on(Storable::ON_BEFORE_UPDATE, $listener);
    }

    /**
     * @return static
     * @throws \Sayla\Exception\Error
     * @throws \Sayla\Objects\Contract\Exception\TriggerError
     */
    public function create()
    {
        if ($this->exists) {
            return $this;
        }
        $instance = $this->getStorable();
        $instance(Storable::ON_BEFORE_SAVE);
        try {
            $instance->storing = true;
            $instance(Storable::ON_BEFORE_CREATE);
            $newAttributes = static::getStore()->create($instance);
            $instance->initStoreValues($newAttributes);
            $instance(Storable::ON_AFTER_CREATE);
            $instance->exists = true;
        } finally {
            $instance->storing = false;
        }
        $instance(Storable::ON_AFTER_SAVE);
        $instance->clearModifiedAttributeFlags();
        return $instance;
    }

    /**
     * @return static
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
            $instance->initStoreValues($newAttributes);
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
     * @return static
     * @throws \Sayla\Exception\Error
     * @throws \Sayla\Objects\Contract\Exception\TriggerError
     */
    public function update()
    {
        $instance = $this->getStorable();
        try {
            $instance(Storable::ON_BEFORE_SAVE);
            $instance->storing = true;
            $instance(Storable::ON_BEFORE_UPDATE);
            $newAttributes = static::getStore()->update($instance);
            $instance->initStoreValues($newAttributes);
            $instance(Storable::ON_AFTER_UPDATE);
            $instance->exists = true;
        } finally {
            $instance->storing = false;
        }
        $instance(Storable::ON_AFTER_SAVE);
        $instance->clearModifiedAttributeFlags();
        return $instance;
    }

    private function initStoreValues(iterable $newRawData)
    {
        if (is_iterable($newRawData) && filled($newRawData)) {
            $validAttrs = $this::dataType()->getDescriptor()->getHydrationMap();
            $hydrated = $this::dataType()->hydrateData($newRawData);
            $subset = array_intersect_key($validAttrs, $newRawData);
            $hydrated = Arr::only($hydrated, $subset);
            $this->init($hydrated);
        }
    }
}