<?php

namespace Sayla\Objects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Sayla\Helper\Data\StandardObject;
use Sayla\Objects\Contract\Attributable;
use Sayla\Util\JsonHelper;

class AttributableObject extends StandardObject implements Attributable
{
    private $attributes = [];

    /**
     * @param iterable $data
     * @return array
     */
    public static function serializeData(iterable $data): array
    {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            } elseif ($value instanceof Jsonable) {
                return JsonHelper::decode($value->toJson(), true);
            } elseif ($value instanceof Arrayable) {
                return $value->toArray();
            } else {
                return $value;
            }
        }, $data);
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

    protected function isRetrievableAttribute(string $attributeName)
    {
        return isset($this->attributes[$attributeName])
            || (static::class != self::class && method_exists($this, 'get' . ucfirst($attributeName) . 'Attribute'));
    }

    public function __unset($name)
    {
        $this->offsetUnset($name);
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
     * @param string $attributeName
     * @param $value
     */
    protected function setAttributeValue(string $attributeName, $value): void
    {
        if (self::class != static::class &&
            method_exists($this, $method = 'set' . studly_case($attributeName) . 'Attribute')
        ) {
            $this->$method($value);
        } else {
            $this->setRawAttribute($attributeName, $value);
        }
    }

    protected function setRawAttribute(string $attributeName, $value)
    {
        $this->attributes[$attributeName] = $value;
    }

    protected function getAttributeValue(string $attributeName)
    {
        $value = $this->getRawAttribute($attributeName);
        if (self::class != static::class) {
            $getterMethod = 'get' . ucfirst($attributeName) . 'Attribute';
            if (method_exists($this, $getterMethod)) {
                return $this->$getterMethod($value);
            }
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

    /**
     * @param iterable $attributeNames
     * @return \Sayla\Objects\AttributableObject
     */
    public function only(iterable $attributeNames)
    {
        $atts = [];
        foreach ($attributeNames as $attributeName)
            $atts[$attributeName] = $this[$attributeName];
        return self::makeFromArray($atts);
    }

    public static function makeFromArray($attributes): self
    {
        $attributableObject = new self;
        $attributableObject->attributes = (array)$attributes;
        return $attributableObject;
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

    public function unserialize($serialized)
    {
        $properties = unserialize($serialized);
        foreach ($properties as $k => $v) {
            $this->attributes[$k] = $v;
        }
    }
}