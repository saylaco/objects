<?php

namespace Sayla\Objects\DataType;


class AttributesContext
{
    /** @var array */
    public $attributes;
    /** @var \Sayla\Objects\DataType\DataTypeDescriptor */
    public $descriptor;

    public function __construct(\Sayla\Objects\DataType\DataTypeDescriptor $descriptor, array $attributes)
    {

        $this->descriptor = $descriptor;
        $this->attributes = $attributes;
    }
}