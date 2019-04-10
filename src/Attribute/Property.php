<?php

namespace Sayla\Objects\Attribute;

use ArrayAccess;
use Sayla\Helper\Data\FreezableObject;
use Sayla\Objects\Contract\Property as PropertyInterface;

class Property extends FreezableObject implements PropertyInterface
{
    /** @var mixed */
    protected $value;
    /** @var string */
    private $name;

    /**
     * @param string $name
     * @param $value
     */
    public function __construct(string $name, $value)
    {
        $this->value = $value;
        $this->name = $name;
        $this->freeze();
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
        ];
    }

    public function __toString()
    {
        return (string)$this->value;
    }

    public function getName(): string
    {
        return $this->name;
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
        if ((is_array($this->value) || $this->value instanceof ArrayAccess) && isset($this->value[$offset])) {
            return $this->value[$offset];
        }
        return parent::offsetGet($offset);
    }

    public function toJson($options = 0)
    {
        return json_encode($this->value, $options);
    }

}