<?php

namespace Sayla\Objects\Contract;

use Sayla\Objects\DataType\DataTypeDescriptor;

interface SupportsObjectDescriptor extends SupportsDataType
{
    public function descriptor(): DataTypeDescriptor;
}