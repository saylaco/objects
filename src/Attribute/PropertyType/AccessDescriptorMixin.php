<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Util\Mixin\Mixin;

class AccessDescriptorMixin implements Mixin
{
    /** @var \Illuminate\Support\Collection|\Sayla\Objects\Contract\Attributes\Property[] */
    private $access;

    /**
     * DataTypeDescriptor constructor.
     * @param \Illuminate\Support\Collection|\Sayla\Objects\Contract\Attributes\Property[] $access
     */
    public function __construct(array $access)
    {
        $this->access = collect($access);
    }

    public function getVisible(): array
    {
        return $this->access->filter->visible->keys()->all();
    }

    public function getHidden(): array
    {
        return $this->access->filter->hidden->keys()->all();
    }

    public function getWritable(): array
    {
        return $this->access->filter->writable->keys()->all();
    }

    public function isHidden(string $attributeName): bool
    {
        return $this->access[$attributeName]['hidden'] ?? false;
    }

    public function isReadable(string $attributeName): bool
    {
        return $this->access[$attributeName]['readable'] ?? false;
    }

    public function isVisible(string $attributeName)
    {
        return $this->access[$attributeName]['visible'] ?? false;
    }

    public function isWritable(string $attributeName): bool
    {
        return $this->access[$attributeName]['writable'] ?? false;
    }
}