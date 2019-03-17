<?php

namespace Sayla\Objects\Contract;

use Sayla\Util\Mixin\Mixin;

interface ProvidesDataTypeDescriptorMixin
{
    public function getDataTypeDescriptorMixin(string $dataType, array $properties): Mixin;
}