<?php

namespace Sayla\Objects;

use DeepCopy\Filter\SetNullFilter;
use DeepCopy\Matcher\PropertyNameMatcher;
use Sayla\Objects\Contract\Keyable;
use Sayla\Objects\Contract\Storable;
use Sayla\Objects\Pipes\NullPipeManager;
use Sayla\Objects\Pipes\PipelinesTrait;
use Sayla\Objects\Pipes\PipeManager;

/**
 * @property callable __beforeCreate Adds an before create trigger to an instance
 * @property callable __beforeUpdate Adds an before update trigger to an instance
 * @property callable __beforeDelete Adds an before delete trigger to an instance
 * @property callable __afterCreate Adds an after create trigger to an instance
 * @property callable __afterUpdate Adds an after update trigger to an instance
 * @property callable __afterDelete Adds an after delete trigger to an instance
 * @method int __beforeCreate() executes callbacks observing a Create
 * @method int __beforeUpdate() executes callbacks observing an Update
 * @method int __beforeDelete() executes callbacks observing a Delete
 * @method int __afterCreate() executes callbacks observing a Create
 * @method int __afterUpdate() executes callbacks observing an Update
 * @method int __afterDelete() executes callbacks observing a Delete
 */
abstract class BaseDataModel extends DataObject implements Keyable, Storable
{
    use PipelinesTrait;
    protected $exists = false;
    private $storing = false;

    /**
     * @param string $class
     * @param iterable $results
     * @return \Sayla\Objects\ObjectCollection|static[]
     */
    public static function hydrateMany(string $class, iterable $results)
    {
        $objectCollection = self::newObjectCollection($class);
        foreach ($results as $i => $result) {
            $objectCollection[$i] = static::hydrateObject($class, (array)$result);
        }
        return $objectCollection;
    }

    /**
     * @param string $class
     * @param iterable $attributes
     * @return static
     */
    final public static function hydrateObject(string $class, iterable $attributes = [])
    {
        return call_user_func($class . '::hydrate', $attributes);
    }

    public static function onCreate($listener, $key = null)
    {
        self::getPipes()->onObjectCreate(static::getPipelineName(), $listener, $key);
    }

    /**
     * @return \Sayla\Objects\Pipes\PipeManager
     */
    public static function getPipes(): PipeManager
    {
        return self::$pipeManager ?? (self::$pipeManager = new NullPipeManager());
    }

    public static function onDelete($listener, $key = null)
    {
        self::getPipes()->onObjectDelete(static::getPipelineName(), $listener, $key);
    }

    public static function onSave($listener, string $key = null)
    {
        self::getPipes()->onObjectCreate(static::getPipelineName(), $listener, $key);
        self::getPipes()->onObjectUpdate(static::getPipelineName(), $listener, $key);
    }

    public static function onUpdate($listener, $key = null)
    {
        self::getPipes()->onObjectUpdate(static::getPipelineName(), $listener, $key);
    }

    /**
     * @return \DeepCopy\DeepCopy
     */
    public function getCopier(): \DeepCopy\DeepCopy
    {
        $copier = parent::getCopier();
        $copier->addFilter(new SetNullFilter(), new PropertyNameMatcher('exists'));
        return $copier;
    }

    protected function realSerializableProperties(): array
    {
        $properties = parent::realSerializableProperties();
        $properties['exists'] = $this->exists;
        return $properties;
    }

    public function getKey()
    {
        $keys = $this->descriptor()->getKeys();
        if (count($keys) == 1) {
            return $this->getRawAttribute($keys[0]);
        }
        return $this->pluck(...$keys);
    }

    public function isStoring()
    {
        return $this->storing;
    }

    public function save()
    {
        if (!$this->exists()) {
            return $this->create();
        }
        return $this->update();
    }

    public function create()
    {
        if ($this->exists) {
            return false;
        }
        $instance = $this->getStoreInstance();
        $instance->storing = true;
        $this->getStore()->create($instance);
        $instance->exists = true;
        $instance->storing = false;
        return $instance;
    }
    /**
     * @return bool
     */
    protected function isTrackingModifiedAttributes(): bool
    {
        return parent::isTrackingModifiedAttributes() && !$this->isStoring();
    }

    public function delete()
    {
        $instance = $this->getStoreInstance();
        $instance->storing = true;
        $this->getStore()->delete($instance);
        $instance->exists = false;
        $instance->storing = false;
        return $instance;
    }

    public function exists()
    {
        return $this->exists;
    }

    /**
     * @param iterable $attributes
     * @return static
     */
    public static function hydrate(iterable $attributes)
    {
        $object = static::make($attributes);
        $object->exists = true;
        return $object;
    }

    public function setKey($value)
    {
        $keys = $this->descriptor()->getKeys();
        if (count($keys) == 1) {
            $this->setRawAttribute($keys[0], $value);
        } else {
            foreach ($keys as $key)
                $this->setRawAttribute($key, $value[$key]);
        }
        return $this;
    }

    public function update()
    {
        $instance = $this->getStoreInstance();
        $instance->storing = true;
        $this->getStore()->update($instance);
        $instance->exists = true;
        $instance->storing = false;
        return $instance;
    }

    protected function getStoreInstance()
    {
        return $this;
    }

    public function getStore(): ObjectStore
    {
        $objectStore = new ObjectStore($this->getStoreStrategy());
        if ($pipes = static::getPipes()) {
            $objectStore->setObserver($pipes);
        }
        return $objectStore;
    }

    /**
     * @return \Sayla\Objects\Contract\StoreStrategy
     */
    public abstract function getStoreStrategy();
}