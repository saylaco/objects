<?php

namespace Sayla\Objects;

use Illuminate\Support\Str;
use Sayla\Objects\Contract\DataObject\SupportsDataTypeManager;
use Sayla\Objects\Contract\DataObject\SupportsDataTypeManagerTrait;
use Sayla\Objects\Contract\Exception\InaccessibleAttribute;
use Sayla\Objects\Contract\IDataObject;
use Sayla\Objects\Contract\Storable;
use Sayla\Objects\Contract\Triggerable;
use Sayla\Objects\Contract\TriggerableTrait;
use Sayla\Objects\DataType\DataType;
use Sayla\Objects\DataType\DataTypeDescriptor;

abstract class DataObject extends AttributableObject implements IDataObject, SupportsDataTypeManager, Triggerable
{
    use SupportsDataTypeManagerTrait;
    use TriggerableTrait;

    const TRIGGER_PREFIX = '__';
    protected static $unguarded = false;
    protected $buildableAttributes = [];
    protected $exists = null;
    private $initializing = false;
    private $modifiedAttributes = [];
    private $resolving = false;
    private $setObjectProperties = false;

    /**
     * @noinspection PhpMissingParentConstructorInspection
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
        $typeName = static::class . '::DATA_TYPE';
        return defined($typeName) ? constant($typeName) : static::class;
    }

    final static public function descriptor(): DataTypeDescriptor
    {
        return self::getDataTypeManager()->getDescriptor(static::dataTypeName());
    }

    static public function makeCollectionResponse($request, $collection)
    {
        return static::dataType()->getResponseFactory()->makeCollectionResponse($request, $collection);
    }

    static public function makeObjectResponse($request, $object)
    {
        return static::dataType()->getResponseFactory()->makeObjectResponse($request, $object);
    }

    /**
     * @return \Sayla\Objects\ObjectCollection|static[]
     */
    public static function newCollection()
    {
        return static::dataType()->newCollection();
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
        if (Str::startsWith($name, self::TRIGGER_PREFIX)) {
            return $this->getTriggerCount(substr($name, 2));
        }
        return $this->getGuardedAttributeValue($name);
    }

    public function __set($name, $value)
    {
        if ($this->setObjectProperties) {
            parent::__set($name, $value);
        } else {
            if (Str::startsWith($name, self::TRIGGER_PREFIX)) {
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

    protected function determineExistence()
    {
        return false;
    }

    /**
     * @return array
     * @throws \Sayla\Objects\Contract\Exception\TransformationError
     */
    public function extract(): array
    {
        return static::dataType()->extract($this);
    }

    protected function getAttributeValue(string $attributeName)
    {
        if (!$this->isAttributeFilled($attributeName) && $this::descriptor()->hasResolver($attributeName)) {
            return $this->getAttributeValueViaGetter($attributeName, $this->resolveAttributeValue($attributeName));
        }
        return $this->getAttributeValueViaGetter($attributeName);
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
        if ($this instanceof Storable) {
            $this->exists = $this->determineExistence();
        }
        $this->initializing = false;
        return $this;
    }

    /**
     * @param iterable $attributes
     */
    protected function initialize($attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->setRawAttribute($key, $value);
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
        return $this::descriptor()->isReadable($attributeName);
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
        return $this::descriptor()->isWritable($attributeName) || !$this->isAttributeSet($attributeName);
    }

    /**
     * @return bool
     */
    public function isInitializing(): bool
    {
        return $this->initializing;
    }

    /**
     * @return bool
     */
    public function isModified(string $attribute): bool
    {
        return isset($this->modifiedAttributes[$attribute]);
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
    public function isTouched(): bool
    {
        return count($this->getModifiedAttributes()) > 0;
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
        $resolving = $this->resolving;
        /** @var \Sayla\Objects\Attribute\PropertyType\ResolverDescriptorMixin $descriptor */
        $descriptor = static::descriptor();
        $this->resolving = true;
        // get values with resolvers
        $values = collect($attributes)
            ->filter(function ($attributeName) use ($descriptor) {
                return $descriptor->hasResolver($attributeName);
            })
            ->flatMap(function ($attributeName) use ($descriptor) {
                $resolver = $descriptor->getResolver($attributeName);
                return [$attributeName => $resolver->resolve($this)];
            });

        $this->init($values->all());
        $this->resolving = $resolving;
        return $this;
    }

    /**
     * @param string $attributeName
     * @return mixed
     * @throws \Sayla\Objects\Contract\Exception\AttributeResolverNotFound
     */
    protected function resolveAttributeValue(string $attributeName)
    {
        $resolving = $this->resolving;
        $this->resolving = true;
        if (!$this::descriptor()->hasResolver($attributeName)) {
            $value = null;
            $this->setRawAttribute($attributeName, $value);
        } else {
            $resolver = $this::descriptor()->getResolver($attributeName);
            $value = $resolver->resolve($this);
            $this->initialize([$attributeName => $value]);
        }
        $this->resolving = $resolving;
        return $value;
    }

    /**
     * @param string[] ...$attributes
     * @return \Illuminate\Support\Collection|mixed[]
     */
    public function runGetters(...$attributes)
    {
        return collect($attributes)
            ->filter(function ($attributeName) {
                return $this->hasAttributeGetter($attributeName);
            })
            ->flatMap(function ($attributeName) {
                return [$attributeName => $this->getAttributeValueViaGetter($attributeName)];
            });
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
        if (in_array($attributeName, $this->buildableAttributes)) {
            $value = static::descriptor()->getTransformer()->build($attributeName, $value);
        }
        parent::setAttributeValue($attributeName, $value);
    }

    /**
     * @param string $attributeName
     * @param $value
     * @throws \Sayla\Objects\Contract\Exception\InaccessibleAttribute
     */
    protected function setGuardedAttributeValue(string $attributeName, $value)
    {
        if (!$this->isAttributeWritable($attributeName)) {
            throw new InaccessibleAttribute(static::dataTypeName(), $attributeName, 'Not writable');
        }
        $this->setAttributeValue($attributeName, $value);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse($request)
    {
        return self::makeObjectResponse($request, $this);
    }

    /**
     * Get items as an array of scalar values
     *
     * @return array
     */
    public function toScalarArray()
    {
        return scalarize($this->toArray());
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
     * @return mixed
     */
    private function getAttributeValueViaGetter(string $attributeName, $value = null)
    {
        $value = func_num_args() > 1 ? $value : $this->getRawAttribute($attributeName);
        if ($this->hasAttributeGetter($attributeName)) {
            return $this->{$this->getAttributeGetter($attributeName)}($value);
        }
        return $value;
    }
}