<?php

namespace Sayla\Objects\Contract;

interface ProvidesDataTypeDescriptorMixin
{
    public function getDataTypeDescriptorMixin(DataType $dataType): Mixin;
}