<?php

namespace Sayla\Objects;

use Illuminate\Support\Collection;
use Sayla\Exception\InvalidValue;

/**
 * @method DataObject[] getIterator
 */
class ObjectCollection extends Collection
{
    protected $itemDescriptor = DataObject::class;
    protected $allowNullItems = false;
    protected $requireItemKey = false;
    protected $keyAttribute;

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
        $collection->itemDescriptor = $descriptor;
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
        return $this->itemDescriptor;
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
            if (!$item instanceof $this->itemDescriptor) {
                $this->push($this->makeObject($item));
            } else {
                $this->push($item);
            }
        }
        return $this;
    }

    /**
     * @param $item
     * @return Object
     */
    protected function makeObject($item)
    {
        return DataObject::makeObject($this->itemDescriptor, (array)$item);
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

    protected function validateItemType($value)
    {
        if ($this->allowNullItems && $value === null) {
            return;
        }
        $itemDescriptor = $this->getItemDescriptor();
        if (!is_a($value, $itemDescriptor->class)
            && !is_subclass_of($value, $itemDescriptor->class)
            && ($value->descriptor()->name != $itemDescriptor->name)
            && ($value->descriptor()->class != $itemDescriptor->class)
        ) {
            throw new InvalidValue("An item must a '{$this->itemDescriptor}' object");
        }
    }

    protected function validateItemKey($key)
    {
        if ($this->requireItemKey && $key === null) {
            throw new InvalidValue('An item must have a non null key');
        }
    }

    public function pluck($value, $key = null)
    {
        return parent::pluck($value, $key)->toBase();
    }

    /**
     * @param array ...$attributes
     * @return static
     */
    public function resolve(...$attributes)
    {
        $itemDescriptor = $this->getItemDescriptor();
        $allAttributes = [];
        foreach ($attributes as $attribute) {
            $values = $itemDescriptor->resolveValues($attribute, $this);
            foreach ($values as $i => $value) {
                $allAttributes[$i][$attribute] = $value;
            }
        }
        if (!empty($allAttributes)) {
            foreach ($this as $i => $object) {
                $object->initializeAttributeValues($allAttributes[$i]);
            }
        }
        return $this;
    }

    /**
     * @return \Sayla\Objects\Inspection\ObjectDescriptor
     */
    protected function getItemDescriptor(): Inspection\ObjectDescriptor
    {
        return DataObject::getDescriptor($this->itemDescriptor);
    }

    public function toPrettyJson()
    {
        return json_encode($this->jsonSerialize(), JSON_PRETTY_PRINT);
    }

    public function jsonSerialize(bool $valuesOnly = false)
    {
        if ($valuesOnly || !$this->isForcingKeys()) {
            return array_values(parent::jsonSerialize());
        }
        return parent::jsonSerialize();
    }

    /**
     * @return bool
     */
    protected function isForcingKeys(): bool
    {
        return $this->keyAttribute != null;
    }

    /**
     * @return Collection|array[]
     */
    public function toScalars()
    {
        return $this->map->toScalarArray()->toBase();
    }

    public function groupBy($groupBy, $preserveKeys = false)
    {
        return parent::groupBy($groupBy, $preserveKeys)->toBase();
    }
}