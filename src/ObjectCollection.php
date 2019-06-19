<?php

namespace Sayla\Objects;

use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Sayla\Data\JavascriptObject;
use Sayla\Exception\InvalidValue;
use Sayla\Helper\Data\Contract\Collectionable;
use Sayla\Objects\Contract\DataObject\SupportsDataTypeManager;
use Sayla\Objects\Contract\DataObject\SupportsDataTypeManagerTrait;
use Sayla\Objects\Contract\IDataObject;
use Sayla\Objects\DataType\DataType;
use Sayla\Objects\DataType\DataTypeDescriptor;
use Sayla\Objects\Support\ObjectCollectionProxy;

/**
 * @method IDataObject[] getIterator
 */
abstract class ObjectCollection extends Collection implements Responsable, Collectionable, SupportsDataTypeManager
{
    use SupportsDataTypeManagerTrait;
    protected static $enforceItemType = true;
    private static $collectionClasses = [];
    protected $allowNullItems = false;
    protected $keyAttribute;
    protected $requireItemKey = false;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct($items = [])
    {
        if (!empty($items)) {
            if ($items instanceof Collection) {
                $this->items = $items->items;
            } else {
                $this->fill($items);
            }
        }
    }

    /**
     * @param string $dataTypeName
     * @return \Sayla\Objects\ObjectCollection
     */
    public static function makeFor(string $dataTypeName)
    {
        if (!isset(self::$collectionClasses[$dataTypeName])) {
            $collection = new class() extends ObjectCollection
            {
                public $dataTypeName;

                /**
                 * @return string
                 */
                public function getDataTypeName(): string
                {
                    return $this->dataTypeName;
                }
            };
            $collection->dataTypeName = $dataTypeName;
            self::$collectionClasses[$dataTypeName] = $collection;
        }
        return clone self::$collectionClasses[$dataTypeName];
    }

    /**
     * @param iterable $objects
     * @return static
     */
    public static function makeUnrestrictedCollection($objects = null)
    {
        $static = (new static())->toUnrestrictedCollection();
        return $objects ? $static->fill($objects) : $static;
    }

    /**
     * @param bool $enforceItemType
     */
    public static function setEnforceItemType(bool $enforceItemType): void
    {
        static::$enforceItemType = $enforceItemType;
    }

    /**
     * Dynamically access collection proxies.
     *
     * @param string $key
     * @return mixed
     *
     * @throws \Exception
     */
    public function __get($key)
    {
        if (!in_array($key, static::$proxies)) {
            throw new Exception("Property [{$key}] does not exist on this collection instance.");
        }

        return new ObjectCollectionProxy($this, $key);
    }

    /**
     * @return \Sayla\Objects\DataType\DataType
     */
    protected function dataType(): DataType
    {
        return self::getDataTypeManager()->get($this->getDataTypeName());
    }

    /**
     * @return \Sayla\Objects\DataType\DataTypeDescriptor
     */
    protected function descriptor(): DataTypeDescriptor
    {
        return $this->dataType()->getDescriptor();
    }

    /**
     * @param $items
     * @return $this
     */
    public function fill(iterable $items)
    {
        foreach ($items as $item) {
            $this->push($item);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getArrayCopy(): array
    {
        return $this->items;
    }

    /**
     * @return string
     */
    public abstract function getDataTypeName(): string;

    public function groupBy($groupBy, $preserveKeys = false)
    {
        $results = $this->toBase()->groupBy($groupBy, $preserveKeys);
        foreach ($results as $i => $tempCollection) {
            if ($tempCollection->first() instanceof DataObject) {
                $collection = clone $this;
                $collection->items = $tempCollection->all();
                $results[$i] = $collection;
            }
        }
        return $results;
    }

    /**
     * @return bool
     */
    protected function isForcingKeys(): bool
    {
        return $this->keyAttribute != null;
    }

    public function jsonSerialize(bool $valuesOnly = false)
    {
        if ($valuesOnly || !$this->isForcingKeys()) {
            return array_values(parent::jsonSerialize());
        }
        return parent::jsonSerialize();
    }

    public function keys()
    {
        return parent::keys()->toBase();
    }

    /**
     * @param $items
     * @return $this
     */
    public function makeObjects($items)
    {
        foreach ($items as $i => $item) {
            if (!$item instanceof IDataObject) {
                if (filled($item)) {
                    $this->push($this->dataType()->hydrate($item));
                }
            } else {
                $this->push($item);
            }
        }
        return $this;
    }

    /**
     * Get an item at a given offset.
     *
     * @param mixed $key
     * @return mixed|null|Object
     */
    public function offsetGet($key): ?IDataObject
    {
        return $this->items[$key];
    }

    /**
     * Set the item at a given offset.
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->validateItemType($value);

        if (is_null($key) && $this->isForcingKeys()) {
            $key = $value->{$this->keyAttribute};
        }

        $this->validateItemKey($key);

        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    public function pluck($value, $key = null)
    {
        return parent::pluck($value, $key)->toBase();
    }

    /**
     * @param array ...$attributes
     * @return static
     * @throws \Sayla\Objects\Contract\Exception\AttributeResolverNotFound
     */
    public function resolve(...$attributes)
    {
        $this->descriptor()->resolveMany($this, $attributes);
        return $this;
    }

    public function toJsObject(): JavascriptObject
    {
        return new JavascriptObject($this->items);
    }

    public function toPrettyJson()
    {
        return json_encode($this->jsonSerialize(), JSON_PRETTY_PRINT);
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function toResponse($request)
    {
        return $this->dataType()
            ->getResponseFactory()
            ->makeCollectionResponse($request, $this);
    }

    /**
     * @return static
     */
    public function toUnrestrictedCollection()
    {
        $static = clone $this;
        $static->items = [];
        $static->allowNullItems = true;
        $static->requireItemKey = false;
        return $static->fill($this->items);
    }

    /**
     * @param string $keyAttribute
     * @return static
     */
    public function useKey(string $keyAttribute)
    {
        $static = clone $this;
        $static->items = [];
        $static->keyAttribute = $keyAttribute;
        return $static->fill($this->items);
    }

    protected function validateItemKey($key)
    {
        if ($this->requireItemKey && $key === null) {
            throw new InvalidValue('An item must have a non null key');
        }
    }

    /**
     * @param \Sayla\Objects\Contract\IDataObject $value
     */
    protected function validateItemType($value)
    {
        if ($this->allowNullItems && $value === null) {
            return;
        }
        if (!static::$enforceItemType) {
            return;
        }
        if ($value::dataTypeName() != $this->getDataTypeName()) {
            $objectClass = $this->dataType()->getObjectClass();
            if (!is_a($value, $objectClass) && !is_subclass_of($value, $objectClass)) {
                throw new InvalidValue(
                    "Item must be a '{$this->getDataTypeName()}' object. Received a "
                    . (is_object($value) ? get_class($value) : gettype($value)));
            }
        }
    }
}