<?php

namespace Sayla\Objects;

use Illuminate\Support\Collection;
use Sayla\Data\JavascriptObject;
use Sayla\Exception\InvalidValue;
use Sayla\Objects\Contract\NonCachableAttribute;
use Sayla\Objects\DataType\DataTypeDescriptor;
use Sayla\Objects\DataType\DataTypeManager;

/**
 * @method DataObject[] getIterator
 */
class ObjectCollection extends Collection
{
    protected $allowNullItems = false;
    protected $dataType = DataObject::class;
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
        $collection->dataType = $descriptor;
        $collection->allowNullItems = $allowNullItems;
        $collection->requireItemKey = $requireItemKey;
        $collection->keyAttribute = $itemKey;
        return $collection;
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
     * @return string
     */
    public function getItemClass(): string
    {
        return $this->dataType;
    }

    /**
     * @return \Sayla\Objects\DataType\DataTypeDescriptor
     */
    protected function getItemDescriptor(): DataTypeDescriptor
    {
        return DataTypeManager::getInstance()->getDescriptor($this->dataType);
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
     * @throws \Sayla\Objects\Exception\HydrationError
     */
    protected function makeObject($item)
    {
        return DataTypeManager::getInstance()->get($this->dataType)->hydrate($item);
    }

    /**
     * @param $items
     * @return $this
     */
    public function makeObjects($items)
    {
        foreach ($items as $i => $item) {
            if (!$item instanceof $this->dataType) {
                $this->push($this->makeObject($item));
            } else {
                $this->push($item);
            }
        }
        return $this;
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed $key
     * @return mixed|null|Object
     */
    public function offsetGet($key): ?DataObject
    {
        return $this->items[$key];
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed $key
     * @param  mixed $value
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
     * @throws \Sayla\Objects\Exception\AttributeResolverNotFound
     */
    public function resolve(...$attributes)
    {
        $itemDescriptor = $this->getItemDescriptor();
        $allAttributes = [];
        foreach ($attributes as $attribute) {
            $resolver = $itemDescriptor->getResolver($attribute);
            if ($resolver instanceof NonCachableAttribute) {
                continue;
            }
            $values = $resolver->resolveMany($this);
            foreach ($values as $i => $value) {
                $allAttributes[$i][$attribute] = $value;
            }
        }
        if (!empty($allAttributes)) {
            foreach ($this as $i => $object) {
                $object->init($allAttributes[$i]);
            }
        }
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

    protected function validateItemKey($key)
    {
        if ($this->requireItemKey && $key === null) {
            throw new InvalidValue('An item must have a non null key');
        }
    }

    protected function validateItemType($value)
    {
        if ($this->allowNullItems && $value === null) {
            return;
        }
        $itemDescriptor = $this->getItemDescriptor();
        if (!is_a($value, $itemDescriptor->getObjectClass())
            && !is_subclass_of($value, $itemDescriptor->getObjectClass())
            && ($value->getDataType() != $itemDescriptor->getDataType())
            && (get_class($value) != $itemDescriptor->getObjectClass())
        ) {
            throw new InvalidValue("An item must a '{$this->dataType}' object");
        }
    }
}