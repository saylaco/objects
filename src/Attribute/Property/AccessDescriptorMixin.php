<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Util\Mixin\Mixin;

class AccessDescriptorMixin implements Mixin
{
    /** @var \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[] */
    private $access;

    /**
     * DataTypeDescriptor constructor.
     * @param \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[] $access
     */
    public function __construct(array $access)
    {
        $this->access = collect($access);
    }

    public function getWritable(): array
    {
        return $this->access->filter->writable->keys()->all();
    }

    public function isReadable(string $attributeName)
    {
        return $this->access[$attributeName]['readable'] ?? false;
    }

    public function isWritable(string $attributeName)
    {
        return $this->access[$attributeName]['writable'] ?? false;
    }
}