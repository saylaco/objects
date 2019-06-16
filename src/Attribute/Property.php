<?php

namespace Sayla\Objects\Attribute;

use ArrayAccess;
use Sayla\Helper\Data\FreezableObject;
use Sayla\Objects\Contract\Attributes\Property as PropertyInterface;

class Property extends FreezableObject implements PropertyInterface
{
    protected $passThruProp = ['name', 'objectClass'];
    /** @var mixed */
    protected $value;
    /** @var string */
    private $name;
    private $objectClass;
    /** @noinspection PhpMissingParentConstructorInspection */

    /**
     * @param string $name
     * @param $value
     */
    public function __construct(string $objectClass, string $name, $value)
    {
        $this->value = $value;
        $this->name = $name;
        $this->objectClass = $objectClass;
        $this->freeze();
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->name,
            'objectClass' => $this->objectClass,
            'value' => $this->value,
        ];
    }

    /**
     * Offset to retrieve
     */
    public function __get($offset)
    {
        return $this->offsetGet($offset);
    }

    public function __toString()
    {
        return (string)$this->value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getObjectClass(): string
    {
        return $this->objectClass;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function jsonSerialize()
    {
        return json_encode($this->value);
    }

    /**
     * Whether a offset exists
     */
    public function offsetExists($offset)
    {
        if ((is_array($this->value) || $this->value instanceof ArrayAccess) && isset($this->value[$offset])) {
            return true;
        }
        return parent::offsetExists($offset);
    }

    /**
     * Offset to retrieve
     */
    public function offsetGet($offset)
    {
        if (in_array($offset, $this->passThruProp)) {
            return parent::offsetGet($offset);
        }
        return $this->value[$offset];
    }

    public function toJson($options = 0)
    {
        return json_encode($this->value, $options);
    }

}