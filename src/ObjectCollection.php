<?php

namespace Sayla\Objects;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Sayla\Data\JavascriptObject;
use Sayla\Exception\InvalidValue;
use Sayla\Helper\Data\Contract\Collectionable;
use Sayla\Objects\Contract\DataObject\ResponsableObject;
use Sayla\Objects\DataType\DataTypeDescriptor;
use Sayla\Objects\DataType\DataTypeManager;

/**
 * @method DataObject[] getIterator
 */
class ObjectCollection extends Collection implements Responsable, Collectionable
{
    protected static $enforceItemType = true;
    protected $allowNullItems = false;
    protected $dataTypeName = DataObject::class;
    protected $keyAttribute;
    protected $requireItemKey = false;

    /**
     * @param string $descriptor
     * @param bool $allowNullItems
     * @param bool $requireItemKey
     * @return static
     */
    public static function makeObjectCollection(string $descriptor, bool $allowNullItems = false,
                                                bool $requireItemKey = false, string $itemKey = null)
    {
        $collection = new static();
        $collection->dataTypeName = $descriptor;
        $collection->allowNullItems = $allowNullItems;
        $collection->requireItemKey = $requireItemKey;
        $collection->keyAttribute = $itemKey;
        return $collection;
    }

    /**
     * @param bool $enforceItemType
     */
    public static function setEnforceItemType(bool $enforceItemType): void
    {
        self::$enforceItemType = $enforceItemType;
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
    public function getDataTypeName(): string
    {
        return $this->dataTypeName;
    }

    /**
     * @return \Sayla\Objects\DataType\DataTypeDescriptor
     */
    public function getItemDescriptor(): DataTypeDescriptor
    {
        return DataTypeManager::resolve()->getDescriptor($this->dataTypeName);
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
     * @param $item
     * @return \Sayla\Objects\DataObject
     * @throws \Sayla\Objects\Contract\Exception\HydrationError
     */
    protected function makeObject($item)
    {
        return DataTypeManager::resolve()->get($this->dataTypeName)->hydrate($item);
    }

    /**
     * @param $items
     * @return $this
     */
    public function makeObjects($items)
    {
        foreach ($items as $i => $item) {
            if (!$item instanceof $this->dataTypeName) {
                if (filled($item)) {
                    $this->push($this->makeObject($item));
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
    public function offsetGet($key): ?DataObject
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
        $itemDescriptor = $this->getItemDescriptor();
        $itemDescriptor->resolve($this, $attributes);
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
        if ($this->first() instanceof ResponsableObject) {
            return new JsonResponse($this->map->getResponseObject($request));
        }
        if ($this->first() instanceof DataObject) {
            return new JsonResponse($this->map->toVisibleObject());
        }
        return new JsonResponse($this->items);
    }

    protected function validateItemKey($key)
    {
        if ($this->requireItemKey && $key === null) {
            throw new InvalidValue('An item must have a non null key');
        }
    }

    /**
     * @param \Sayla\Objects\DataObject|mixed $value
     */
    protected function validateItemType($value)
    {
        if ($this->allowNullItems && $value === null) {
            return;
        }
        if (!self::$enforceItemType) {
            return null;
        }
        $itemDescriptor = $this->getItemDescriptor();
        if (!is_a($value, $itemDescriptor->getObjectClass())
            && !is_subclass_of($value, $itemDescriptor->getObjectClass())
            && ($value->dataTypeName() != $itemDescriptor->getDataType())
            && (get_class($value) != $itemDescriptor->getObjectClass())
        ) {
            throw new InvalidValue("An item must a '{$this->dataTypeName}' object");
        }
    }
}