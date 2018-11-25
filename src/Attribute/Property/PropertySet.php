<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Helper\Data\Contract\FreezableTrait;
use Sayla\Objects\AttributableObject;
use Sayla\Objects\Contract\Property as PropertyInterface;

class PropertySet extends AttributableObject implements PropertyInterface, \Countable
{
    use FreezableTrait;
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
    public function __construct(string $typeHandle, string $name, array $value)
    {
        $this->typeHandle = $typeHandle;
        $this->name = $name;
        $this->setAttributes($value);
        $this->freeze();
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->name,
            'typeHandle' => $this->typeHandle,
            'value' => $this->getValue(),
        ];
    }

    public function count()
    {
        return count($this->toArray());
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
        return $this->toArray();
    }
}