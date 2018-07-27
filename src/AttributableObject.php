<?php

namespace Sayla\Objects;

use Sayla\Objects\Contract\Attributable;

class AttributableObject implements Attributable
{
    private $attributes = [];

    /**
     * AttributableObject constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param iterable $attributes
     * @return $this
     */
    public function fill(iterable $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->setAttributeValue($key, $value);
        }
        return $this;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->attributes);
    }

    /**
     * @param string $attributeName
     * @return bool
     */
    public function isAttributeSet(string $attributeName): bool
    {
        return array_key_exists($attributeName, $this->attributes);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return self::serializeData($this->attributes);
    }

    public function offsetExists($offset)
    {
        return $this->isRetrievableAttribute($offset);
    }

    public function offsetGet($offset)
    {
        return $this->getAttributeValue($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->setAttributeValue($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->removeAttribute($offset);
    }

    public function pluck(...$attributeNames)
    {
        if (func_num_args() == 1 && is_array($attributeNames[0])) {
            $attributeNames = $attributeNames[0];
        }
        return array_only($this->attributes, $attributeNames);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Get the collection of items as JSON.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * @param string $attributeName
     * @param $value
     */
    protected function setAttributeValue(string $attributeName, $value): void
    {
        if (self::class != static::class
            && $this->hasAttributeSetter($attributeName)) {
            $this->{$this->getAttributeSetter($attributeName)}($value);
        } else {
            $this->setRawAttribute($attributeName, $value);
        }
    }

    /**
     * @param string $attributeName
     * @return string
     */
    public function hasAttributeSetter(string $attributeName): bool
    {
        return method_exists($this, $this->getAttributeSetter($attributeName));
    }

    /**
     * @param string $attributeName
     * @return string
     */
    public function getAttributeSetter(string $attributeName): string
    {
        return $setterMethod = 'set' . ucfirst($attributeName) . 'Attribute';
    }

    protected function setRawAttribute(string $attributeName, $value)
    {
        $this->attributes[$attributeName] = $value;
    }

    /**
     * @param iterable $data
     * @return array
     */
    public static function serializeData(iterable $data): array
    {
        $serialized = [];
        foreach ($data as $k => $v)
            $serialized[$k] = simple_value($v);
        return $serialized;
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function __set($name, $value)
    {
        return $this->offsetSet($name, $value);
    }

    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    /**
     * @param string $attributeName
     * @return string
     */
    public function getAttributeGetter(string $attributeName): string
    {
        return $getterMethod = 'get' . ucfirst($attributeName) . 'Attribute';
    }

    protected function getAttributeValue(string $attributeName)
    {
        $value = $this->getRawAttribute($attributeName);
        if ($this->hasAttributeGetter($attributeName)) {
            return $this->{$this->getAttributeGetter($attributeName)}($value);
        }
        return $value;
    }

    /**
     * @param string $attributeName
     * @return mixed
     */
    protected function getRawAttribute(string $attributeName)
    {
        return $this->attributes[$attributeName] ?? null;
    }

    /**
     * @param string $attributeName
     * @return string
     */
    public function hasAttributeGetter(string $attributeName): bool
    {
        return method_exists($this, $this->getAttributeGetter($attributeName));
    }

    /**
     * @param string $attributeName
     * @return bool
     */
    public function isAttributeFilled(string $attributeName): bool
    {
        return isset($this->attributes[$attributeName]);
    }

    /**
     * @param string $attributeName
     * @return bool
     */
    public function isAttributeValueSet(string $attributeName): bool
    {
        return isset($this->attributes[$attributeName]);
    }

    protected function isRetrievableAttribute(string $attributeName)
    {
        return isset($this->attributes[$attributeName])
            || (static::class != self::class && $this->hasAttributeGetter($attributeName));
    }

    /**
     * @param iterable $attributeNames
     * @return \Sayla\Objects\AttributableObject
     */
    public function only(iterable $attributeNames)
    {
        $atts = [];
        foreach ($attributeNames as $attributeName)
            $atts[$attributeName] = $this[$attributeName];
        return self::make($atts);
    }

    /**
     * @param string $attribute
     */
    protected function removeAttribute(string $attribute)
    {
        unset($this->attributes[$attribute]);
    }

    public function serialize()
    {
        return serialize($this->attributes);
    }

    protected function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function unserialize($serialized)
    {
        $properties = unserialize($serialized);
        foreach ($properties as $k => $v) {
            $this->attributes[$k] = $v;
        }
    }
}