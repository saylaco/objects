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
use Throwable;

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
    private $indexByKeyAttribute = false;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct($items = [])
    {
        if (!empty($items)) {
            if ($items instanceof Collection) {
                $this->items = $items->items;
            } else {
                $this->putItems($items);
            }
        }
    }

    /**
     * @param string $dataTypeName
     * @return \Sayla\Objects\ObjectCollection|string
     */
    private static function makeDynamicCollection(string $dataTypeName): string
    {
        $className = str_replace('\\', '_', __NAMESPACE__) . '_Collections_' . str_replace('\\', '', $dataTypeName);
        $keyAttribute = null;
        try {
            $dt = self::getDataTypeManager()->get($dataTypeName);
            if ($dt->supportsLookup()) {
                $keyAttribute = $dt->getObjectLookup()->getKeyName();
            } elseif ($dt->isAttribute('id')) {
                $keyAttribute = 'id';
            } elseif ($dt->isAttribute('key')) {
                $keyAttribute = 'key';
            }
        } catch (Throwable $e) {

        }
        eval(sprintf('class %s extends %s
            {
                const DATA_TYPE_NAME = "%s";    
                protected $keyAttribute = %s;

                /**
                 * @return string
                 */
                public function getDataTypeName(): string
                {
                    return self::DATA_TYPE_NAME;
                }
            };', $className, self::class, $dataTypeName, var_str($keyAttribute)));
        return $className;
    }

    /**
     * @param string $dataTypeName
     * @return \Sayla\Objects\ObjectCollection
     */
    public static function makeFor(string $dataTypeName)
    {
        if (!isset(self::$collectionClasses[$dataTypeName])) {
            self::$collectionClasses[$dataTypeName] = self::makeDynamicCollection($dataTypeName);
        }
        return new self::$collectionClasses[$dataTypeName];
    }

    /**
     * @param iterable $objects
     * @return static
     */
    public static function makeUnrestrictedCollection($objects = null)
    {
        $static = (new static())->toUnrestrictedCollection();
        return $objects ? $static->pushItems($objects) : $static;
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

    public function alwaysIndexByKey(bool $flag = true)
    {
        $this->indexByKeyAttribute = $flag;
        return $this;
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

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getObjectKeys()
    {
        if ($this->keyAttribute) {
            return $this->pluck($this->keyAttribute);
        }
        return $this->keys();
    }

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

    public function index()
    {
        return $this->keyBy($this->keyAttribute);
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

    public function makeObjects($items)
    {
        foreach ($items as $item) {
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
     * Run a map over each of the items.
     *
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback)
    {
        $keys = array_keys($this->items);

        $items = array_map($callback, $this->items, $keys);

        $first = head($items);

        if (!$first instanceof IDataObject) {
            return collect(array_combine($keys, $items));
        }

        return new static(array_combine($keys, $items));
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

        if (is_null($key) && $this->indexByKeyAttribute && $this->isForcingKeys()) {
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
        return $this->toBase()->pluck($value, $key);
    }

    /**
     * @param $items
     * @return $this
     */
    public function pushItems(iterable $items)
    {
        foreach ($items as $i => $item) {
            $this->push($item);
        }
        return $this;
    }

    /**
     * @param $items
     * @return $this
     */
    public function putItems(iterable $items)
    {
        foreach ($items as $i => $item) {
            $this->put($i, $item);
        }
        return $this;
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
        return $static->pushItems($this->items);
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
        return $static->putItems($this->items);
    }

    protected function validateItemKey($key)
    {
        if ($this->indexByKeyAttribute && $key === null) {
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