<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Util\Mixin\Mixin;

class DefaultDescriptorMixin implements Mixin
{
    /**
     * @var array
     */
    private $properties;

    /**
     *  constructor.
     */
    public function __construct(array $properties)
    {
        $this->properties = $properties;
    }

    public function getDefaultValues(): array
    {
        $defaultValues = [];
        foreach ($this->properties as $k => $v) {
            $defaultValues[$k] = value($v->defaultValue);
        }
        return $defaultValues;
    }
}