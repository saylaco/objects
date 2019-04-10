<?php

namespace Sayla\Objects\Transformers;


interface AttributeValueTransformer extends ValueTransformer
{
    public function getVarType(): string;
}