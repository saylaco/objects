<?php

namespace Sayla\Objects\Attribute\PropertyType;

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

    public function getVisible(): array
    {
        return array_keys($this->properties);
    }

    public function isHidden(string $attributeName): bool
    {
        return !$this->properties[$attributeName];
    }

    public function isVisible(string $attributeName)
    {
        return $this->properties[$attributeName];
    }

}