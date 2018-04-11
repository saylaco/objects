<?php

namespace Sayla\Objects;

use DeepCopy\DeepCopy;
use DeepCopy\Filter\SetNullFilter;
use DeepCopy\Matcher\PropertyNameMatcher;
use DeepCopy\TypeFilter\ReplaceFilter;
use DeepCopy\TypeMatcher\TypeMatcher;
use Sayla\Helper\Data\StandardObject;
use Sayla\Objects\Contract\Attributable;
use Sayla\Objects\Exception\HydrationError;
use Sayla\Objects\Exception\InaccessibleAttribute;
use Sayla\Objects\Exception\UndefinedAttribute;
use Sayla\Objects\Transformers\Transformer;

class DataObject extends AttributableObject implements \Serializable, Attributable
{
    use DefinableAttributesTrait;
    use TriggerableTrait;
    const TRIGGER_PREFIX = '__';
    protected static $unguarded = false;
    protected static $transformUndefinedAttributes = false;
    /** @var  \Sayla\Objects\Transformers\Transformer */
    protected static $transformer;
    private $initializing = false;
    private $resolving = false;
    private $setObjectProperties = false;
    private $modifiedAttributes = [];

    /**
     * @param array $attributes
     */
    public function __construct($attributes = null)
    {
        $this->init($attributes ?? []);
    }

    /**
     * @param iterable $attributes
     * @return \Sayla\Objects\DataObject
     */
    final public function init(iterable $attributes): self
    {
        $this->initializing = true;
        $this->setAttributes($this->descriptor()->getDefaultValues());
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

    protected static function determineObjectClass($attributes = [])
    {
        return static::class;
    }

    /**
     * @param string $name
     * @param iterable $attributes
     * @return \Sayla\Objects\DataObject|static
     */
    public static function makeObject(string $name, iterable $attributes = []): DataObject
    {
        return self::getDescriptors()->makeObject($name, $attributes);
    }

    /**
     * @param string $objectClass
     * @return \Sayla\Objects\ObjectCollection|static[]
     */
    public static function newObjectCollection(string $objectClass = null)
    {
        if (empty($objectClass)) {
            $objectClass = static::class;
        }
        return (new $objectClass)->newCollection();
    }

    public static function transformWith(Transformer $transformer, callable $callback)
    {
        static::$transformer = $transformer;
        try {
            return $callback();
        } finally {
            static::$transformer = null;
        }
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

    public function __call($name, $arguments)
    {
        if (starts_with($name, self::TRIGGER_PREFIX)) {
            return $this->fireTriggers(substr($name, 2), $arguments);
        }
        throw new \BadMethodCallException('Method does not exist - ' . static::class . '::' . $name);
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
        if (!$this->isAttributeSet($attributeName)) {
            $this->resolveAttributeValue($attributeName);
        }
        return $this->runGetFilters($attributeName, parent::getAttributeValue($attributeName));
    }

    protected function isRetrievableAttribute(string $attributeName)
    {
        return parent::isRetrievableAttribute($attributeName) || $this->isAttributeReadable($attributeName);
    }

    /**
     * @param $attributes
     * @return static
     * @throws \Sayla\Exception\Error
     */
    final public static function make($attributes = [])
    {
        try {
            $object = self::getDescriptors()->makeObject(static::class, $attributes);
        } catch (\Throwable $exception) {
            throw (new HydrationError(static::class . ' - ' . $exception->getMessage(), $exception))
                ->withErrorLog($exception);
        }
        return $object;
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
        $getterMethod = 'get' . ucfirst($attributeName) . 'Attribute';
        $getterMethodExists = method_exists($this, $getterMethod);
        if (!$this->descriptor()->isAttribute($attributeName) && $getterMethodExists) {
            return $this->$getterMethod();
        }
        if (!$this->isAttributeReadable($attributeName)) {
            throw new InaccessibleAttribute(static::class, $attributeName, 'Not readable');
        }
        $value = $this->getAttributeValue($attributeName);

        if ($getterMethodExists) {
            $value = $this->$getterMethod($value);
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
     * @return mixed|null
     */
    protected function resolveAttributeValue(string $attributeName)
    {
        $this->resolving = true;
        if ($this->descriptor()->hasDefaultValue($attributeName)) {
            $value = $this->descriptor()->getDefaultValue($attributeName);
        } else {
            $value = $this->descriptor()->resolveValue($attributeName, $this);
        }
        $this->setRawAttribute($attributeName, $value);
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
    public function isInitializing(): bool
    {
        return $this->initializing;
    }

    public function isResolving(): bool
    {
        return $this->resolving;
    }

    public function __invoke(string $name, ...$arguments)
    {
        return $this->fireTriggers($name, $arguments);
    }

    /**
     * @return \DeepCopy\DeepCopy
     */
    public function getCopier(): \DeepCopy\DeepCopy
    {
        $copier = new DeepCopy();
        $copier->skipUncloneable(true);
        $copier->addTypeFilter(new ReplaceFilter(function (self $value) {
            return $value->getCopy();
        }), new TypeMatcher(self::class));
        foreach ($this->descriptor()->getKeys() as $key)
            $copier->addFilter(new SetNullFilter, new PropertyNameMatcher($key));
        foreach (array_keys($this->aliases) as $key) {
            $copier->addFilter(new SetNullFilter, new PropertyNameMatcher($key));
        }
        return $copier;
    }

    /**
     * @return static
     * @throws \Sayla\Exception\Error
     */
    public function getCopy()
    {
        $simpleObject = $this->getCopier()->copy(StandardObject::make($this->toArray()));
        return static::make((array)$simpleObject);
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
     * Fill the object with an array of attributes.
     *
     * @param  array|\Traversable $attributes
     * @return $this
     */
    public function initStoreData($attributes)
    {
        $attributes = $this->getTransformer()->buildOnly($attributes, $this);
        return $this->init($attributes);
    }

    /**
     * @return Transformer
     */
    public function getTransformer(): Transformer
    {
        if (isset(static::$transformer)) {
            return static::$transformer;
        }
        $transformer = $this->descriptor()->getTransformer();
        $transformer->skipNonAttributes(static::$transformUndefinedAttributes);
        return $transformer;
    }

    /**
     * @param $attributes
     */
    public function initializeAttributeValues($attributes): void
    {
        $this->initializing = true;
        $this->initialize($attributes);
        $this->initializing = false;
    }

    /**
     * @return bool
     */
    protected function isTrackingModifiedAttributes(): bool
    {
        return !$this->isInitializing() && !$this->isResolving();
    }

    /**
     * @return ObjectCollection
     */
    public function newCollection()
    {
        return ObjectCollection::makeObjectCollection(static::class);
    }

    public function resolveUnknownAttribute(string $attributeName)
    {
        throw new UndefinedAttribute(get_class($this), $attributeName);
    }

    /**
     * Get items as an array of scalar values
     *
     * @return array
     */
    public function toScalarArray()
    {
        return $this->getTransformer()->skipNonAttributes()->smashAll($this->toArray());
    }

    /**
     * @return \Sayla\Objects\AttributableObject
     */
    public function toStoreObject()
    {
        return AttributableObject::makeFromArray($this->descriptor()->getPersistentAttributes($this));
    }

    /**
     * @return \Sayla\Objects\AttributableObject
     */
    public function toVisibleObject()
    {
        return AttributableObject::makeFromArray($this->toVisibleArray());
    }

    /**
     * @return array
     */
    public function toVisibleArray(): array
    {
        return $this->pluck(...$this->descriptor()->getVisible());
    }

    /**
     * Get visible items as an array of scalar values
     *
     * @return array
     */
    public function toVisibleScalarArray()
    {
        return $this->getTransformer()->smashAll($this->toVisibleArray());
    }

    public function trigger(string $name, ...$arguments)
    {
        return $this->fireTriggers($name, $arguments);
    }

    public function clearModifiedAttributeFlags()
    {
        $this->modifiedAttributes = [];
    }
}