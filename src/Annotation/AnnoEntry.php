<?php

namespace Sayla\Objects\Annotation;

use Sayla\Helper\Data\FreezableObject;

class AnnoEntry extends FreezableObject
{
    /** @var mixed */
    protected $modifier;
    /**  @var string */
    protected $name;
    /** @var array */
    protected $properties;
    /** @var mixed */
    protected $value;

    /**
     * Annotation constructor.
     * @param mixed $value
     * @param mixed $modifier
     * @param array $properties
     */
    final public function __construct(string $name, $value, $modifier, array $properties)
    {
        parent::__construct(compact('name', 'value', 'modifier', 'properties'));
        $this->init();
        $this->freeze();
    }

    /**
     * @return mixed
     */
    public function getModifier()
    {
        return $this->modifier;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    protected function init()
    {

    }
}