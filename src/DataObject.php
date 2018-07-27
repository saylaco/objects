<?php

namespace Sayla\Objects;

use Sayla\Objects\Contract\Attributable;
use Sayla\Objects\Contract\NonCachableAttribute;
use Sayla\Objects\Contract\SupportsDataType;
use Sayla\Objects\Contract\SupportsDataTypeManager;
use Sayla\Objects\Contract\SupportsObjectDescriptorTrait;
use Sayla\Objects\Contract\Triggerable;
use Sayla\Objects\Contract\TriggerableTrait;
use Sayla\Objects\Exception\InaccessibleAttribute;

class DataObject extends AttributableObject
    implements \Serializable, Attributable, SupportsDataType, SupportsDataTypeManager, Triggerable
{
    use SupportsObjectDescriptorTrait;
    use TriggerableTrait;
    const TRIGGER_PREFIX = '__';
    protected static $unguarded = false;
    private $initializing = false;
    private $resolving = false;
    private $setObjectProperties = false;
    private $modifiedAttributes = [];

    /**
     * @param array $attributes
     */
    public function __construct($attributes = null)
    {
        if ($attributes !== null) {
            $this->init($attributes);
        }
    }

    /**
     * @param iterable $attributes
     * @return \Sayla\Objects\DataObject
     */
    final public function init(iterable $attributes): self
    {
        $this->initializing = true;
        $this->initialize($attributes);
        $this->initializing = false;
        return $this;
    }

    /**
     * @param $attributes
     */
    protected function initialize($attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->setRawAttribute($key, $this->runSetFilters($key, $value));
        }
    }

    /**
     * @param string $attributeName
     * @param        $value
     * @return mixed
     */
    private function runSetFilters(string $attributeName, $value)
    {
        foreach ($this->descriptor()->getSetFilters($attributeName) as $callable) {
            if (is_string($callable) && starts_with($callable, '@')) {
                $callable = [$this, substr($callable, 1)];
            }
            $value = call_user_func($callable, $value);
        }
        return $value;
    }

    public static function newObjectCollection()
    {
        if (self::getDataTypeManager()->has(static::class)) {
            return self::getDataTypeManager()->get(static::class)->newCollection();
        }
        return ObjectCollection::make(static::class);
    }

    public static function unguarded(callable $callback)
    {
        if (static::$unguarded) {
            return $callback();
        }
        static::unguard();
        try {
            return $callback();
        } finally {
            static::reguard();
        }
    }

    /**
     * Disable all mass assignable restrictions.
     *
     * @param  bool $state
     * @return void
     */
    public static function unguard($state = true)
    {
        static::$unguarded = $state;
    }

    /**
     * Enable the mass assignment restrictions.
     *
     * @return void
     */
    public static function reguard()
    {
        static::$unguarded = false;
    }

    public function __get($name)
    {
        if (starts_with($name, self::TRIGGER_PREFIX)) {
            return $this->getTriggerCount(substr($name, 2));
        }
        return $this->getGuardedAttributeValue($name);
    }

    public function __set($name, $value)
    {
        if ($this->setObjectProperties) {
            parent::__set($name, $value);
        } else {
            if (starts_with($name, self::TRIGGER_PREFIX)) {
                $this->addTrigger(substr($name, 2), $value);
            } else {
                $this->setGuardedAttributeValue($name, $value);
            }
        }
    }

    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    protected function getAttributeValue(string $attributeName)
    {
        if (!$this->isAttributeSet($attributeName) && $this->descriptor()->hasResolver($attributeName)) {
            $this->resolveAttributeValue($attributeName);
        }
        $value = $this->getRawAttribute($attributeName);
        $value = $this->runGetFilters($attributeName, $value);
        if ($this->hasAttributeGetter($attributeName)) {
            return $this->{$this->getAttributeGetter($attributeName)}($value);
        }
        return $value;
    }

    protected function isRetrievableAttribute(string $attributeName)
    {
        return parent::isRetrievableAttribute($attributeName) || $this->isAttributeReadable($attributeName);
    }

    public function offsetGet($offset)
    {
        return $this->getGuardedAttributeValue($offset);
    }

    public function offsetSet($offset, $value)
    {
        if ($this->setObjectProperties) {
            if ($offset == 'attributes') {
                $this->setAttributes($value);
            } else {
                $this->{$offset} = $value;
            }
        } else {
            if ($offset == 'attributes') {
                debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            }
            $this->setGuardedAttributeValue($offset, $value);
        }
    }

    public function serialize()
    {
        $properties = $this->realSerializableProperties();
        return serialize($properties);
    }

    protected function setAttributeValue(string $attributeName, $value): void
    {
        if ($this->isTrackingModifiedAttributes()) {
            $this->modifiedAttributes[$attributeName] = $attributeName;
        }
        parent::setAttributeValue($attributeName, $this->runSetFilters($attributeName, $value));
    }

    public function unserialize($serialized)
    {
        $this->setObjectProperties = true;
        $properties = unserialize($serialized);
        foreach ($properties as $k => $v) {
            if ($k == 'attributes') {
                $this->setAttributes($v);
            } else {
                $this->{$k} = $v;
            }
        }
        $this->setObjectProperties = false;
    }

    protected function getGuardedAttributeValue(string $attributeName)
    {
        if (!$this->isRetrievableAttribute($attributeName)) {
            throw new InaccessibleAttribute(static::class, $attributeName, 'Not readable');
        }
        return $this->getAttributeValue($attributeName);
    }

    /**
     * @param string $attributeName
     * @param $value
     * @throws \Sayla\Objects\Exception\InaccessibleAttribute
     */
    protected function setGuardedAttributeValue(string $attributeName, $value)
    {
        if (!$this->isAttributeWritable($attributeName)) {
            throw new InaccessibleAttribute(static::class, $attributeName, 'Not writable');
        }
        $this->setAttributeValue($attributeName, $value);
    }

    /**
     * @param string $attributeName
     * @return bool
     */
    public function isAttributeWritable(string $attributeName): bool
    {
        if (static::$unguarded) {
            return true;
        }
        return $this->descriptor()->isWritable($attributeName);
    }

    /**
     * @param string $attributeName
     * @return mixed
     * @throws \Sayla\Objects\Exception\AttributeResolverNotFound
     */
    protected function resolveAttributeValue(string $attributeName)
    {
        $this->resolving = true;
        if (!$this->descriptor()->hasResolver($attributeName)) {
            $value = null;
            $this->setRawAttribute($attributeName, $value);
        } else {
            $resolver = $this->descriptor()->getResolver($attributeName);
            $value = $resolver->resolve($this);
            if (!($resolver instanceof NonCachableAttribute)) {
                $this->setRawAttribute($attributeName, $value);
            }
        }
        $this->resolving = false;
        return $value;
    }

    /**
     * @param string $attributeName
     * @param        $value
     * @return mixed
     */
    private function runGetFilters(string $attributeName, $value)
    {
        foreach ($this->descriptor()->getGetFilters($attributeName) as $callable) {
            if (is_string($callable) && starts_with($callable, '@')) {
                $callable = [$this, substr($callable, 1)];
            }
            $value = call_user_func($callable, $value);
        }
        return $value;
    }

    /**
     * @param string $attributeName
     * @return bool
     */
    public function isAttributeReadable(string $attributeName): bool
    {
        if (static::$unguarded) {
            return true;
        }
        return $this->descriptor()->isReadable($attributeName);
    }

    /**
     * @return array
     */
    protected function realSerializableProperties(): array
    {
        $properties = [];
        $properties['attributes'] = $this->toArray();
        $properties['initializing'] = $this->initializing;
        return $properties;
    }

    /**
     * @return bool
     */
    protected function isTrackingModifiedAttributes(): bool
    {
        return !$this->isInitializing() && !$this->isResolving();
    }

    /**
     * @return bool
     */
    public function isInitializing(): bool
    {
        return $this->initializing;
    }

    public function isResolving(): bool
    {
        return $this->resolving;
    }

    public function clearModifiedAttributeFlags()
    {
        $this->modifiedAttributes = [];
    }

    /**
     * @return mixed[]
     */
    public function getModifiedAttributeNames(): array
    {
        return $this->modifiedAttributes;
    }

    /**
     * @return mixed[]
     */
    public function getModifiedAttributes(): array
    {
        return array_only($this->toArray(), $this->modifiedAttributes);
    }

    /**
     * Get items as an array of scalar values
     *
     * @return array
     */
    public function toScalarArray()
    {
        return simple_value($this->toArray());
    }

    /**
     * @return \Sayla\Objects\AttributableObject
     */
    public function toVisibleObject()
    {
        return AttributableObject::make($this->toVisibleArray());
    }

    /**
     * @return array
     */
    public function toVisibleArray(): array
    {
        return array_only($this->toArray(), $this->descriptor()->getVisible());
    }

    /**
     * Get visible items as an array of scalar values
     *
     * @return array
     */
    public function toVisibleScalarArray()
    {
        return simple_value($this->toVisibleArray());
    }
}