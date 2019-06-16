<?php

namespace Sayla\Objects;

use Sayla\Objects\Contract\DataObject\SupportsDataTypeManager;
use Sayla\Objects\Contract\DataObject\SupportsDataTypeManagerTrait;
use Sayla\Objects\Contract\IDataObject;
use Sayla\Objects\Contract\Storable;
use Sayla\Objects\Contract\Triggerable;
use Sayla\Objects\Contract\TriggerableTrait;
use Sayla\Objects\DataType\DataType;
use Sayla\Objects\DataType\DataTypeDescriptor;
use Sayla\Objects\Exception\InaccessibleAttribute;

abstract class DataObject extends AttributableObject implements IDataObject, SupportsDataTypeManager, Triggerable
{
    use SupportsDataTypeManagerTrait;
    use TriggerableTrait;

    protected const DATA_TYPE = null;
    const TRIGGER_PREFIX = '__';
    protected static $unguarded = false;

    private $initializing = false;
    private $modifiedAttributes = [];
    private $resolving = false;
    private $setObjectProperties = false;

    /**
     * @param array $attributes
     */
    public function __construct($attributes = null)
    {
        if ($attributes !== null) {
            $this->init($attributes);
        }
    }

    final static public function dataType(): DataType
    {
        return self::getDataTypeManager()->get(static::dataTypeName());
    }

    public static function dataTypeName(): string
    {
        return static::DATA_TYPE ?? static::class;
    }

    final static public function descriptor(): DataTypeDescriptor
    {
        return self::getDataTypeManager()->getDescriptor(static::dataTypeName());
    }

    public static function newObjectCollection()
    {
        return static::descriptor()->newCollection();
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

    /**
     * Disable all mass assignable restrictions.
     *
     * @param bool $state
     * @return void
     */
    public static function unguard($state = true)
    {
        static::$unguarded = $state;
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

    public function clearModifiedAttributeFlags(): void
    {
        $this->modifiedAttributes = [];
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

    protected function getGuardedAttributeValue(string $attributeName)
    {
        if (!$this->isRetrievableAttribute($attributeName)) {
            throw new InaccessibleAttribute(static::dataTypeName(), $attributeName, 'Not readable');
        }
        return $this->getAttributeValue($attributeName);
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
     * @param iterable $attributes
     * @return $this
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

    protected function isRetrievableAttribute(string $attributeName)
    {
        return parent::isRetrievableAttribute($attributeName) || $this->isAttributeReadable($attributeName);
    }

    /**
     * @return bool
     */
    protected function isTrackingModifiedAttributes(): bool
    {
        return !$this->isInitializing() && !$this->isResolving();
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

    public function resolve(...$attributes)
    {
        $descriptor = $this->descriptor();
        $this->resolving = true;
        $model = $this;
        $values = collect($attributes)->flatMap(function ($attributeName) use ($descriptor, $model) {
            if ($descriptor->hasResolver($attributeName)) {
                $resolver = $descriptor->getResolver($attributeName);
                if (!($resolver instanceof NonCachableAttribute)) {
                    return [$attributeName => $resolver->resolve($model)];
                }
            }
            return [];
        });
        $this->init($values->all());
        $this->resolving = false;
        return $this;
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
                $this->init([$attributeName => $value]);
            }
        }
        $this->resolving = false;
        return $value;
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

    /**
     * @param string $attributeName
     * @param $value
     * @throws \Sayla\Objects\Exception\InaccessibleAttribute
     */
    protected function setGuardedAttributeValue(string $attributeName, $value)
    {
        if (!$this->isAttributeWritable($attributeName)) {
            throw new InaccessibleAttribute(static::dataTypeName(), $attributeName, 'Not writable');
        }
        $this->setAttributeValue($attributeName, $value);
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
     * @return array
     */
    public function toVisibleArray(): array
    {
        return array_only($this->toArray(), $this->descriptor()->getVisible());
    }

    /**
     * @return \Sayla\Objects\AttributableObject
     */
    public function toVisibleObject()
    {
        return AttributableObject::make($this->toVisibleArray());
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
}