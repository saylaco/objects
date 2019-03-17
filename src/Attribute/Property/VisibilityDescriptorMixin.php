<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Util\Mixin\Mixin;

class VisibilityDescriptorMixin implements Mixin
{
    private $properties;

    /**
     *  constructor.
     * @param $properties
     */
    public function __construct(array $properties)
    {
        $this->properties = $properties;
    }

    public function getVisible()
    {
        return array_keys($this->properties);
    }

    public function isHidden(string $attributeName)
    {
        return !$this->properties[$attributeName];
    }

    public function isVisible(string $attributeName)
    {
        return $this->properties[$attributeName];
    }

}