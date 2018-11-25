<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Helper\Data\FreezableObject;
use Sayla\Objects\Contract\Property as PropertyInterface;

class Property extends FreezableObject implements PropertyInterface
{
    /** @var mixed */
    protected $value;
    /** @var string */
    private $typeHandle;
    /** @var string */
    private $name;

    /**
     * Property constructor.
     * @param mixed $value
     * @param string $typeHandle
     * @param string $name
     */
    public function __construct(string $typeHandle, string $name, $value)
    {
        $this->value = $value;
        $this->typeHandle = $typeHandle;
        $this->name = $name;
        $this->freeze();
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->name,
            'typeHandle' => $this->typeHandle,
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

    public function getTypeHandle(): string
    {
        return $this->typeHandle;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->value, $options);
    }

    public function jsonSerialize()
    {
        return json_encode($this->value);
    }
}