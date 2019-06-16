<?php

namespace Sayla\Objects\Attribute;

use Illuminate\Support\Traits\Macroable;
use Sayla\Objects\Attribute\Resolver\Has;

class AttributeResolverFactory
{
    use Macroable;

    public function hasMany(string $associatedDataType, string $lookupValueAttr = null, string $lookupAttr = null)
    {
        return (new Has($associatedDataType, $lookupAttr, $lookupValueAttr))->multiple();
    }

    public function hasOne(string $associatedDataType, string $lookupValueAttr = null, string $lookupAttr = null)
    {
        return new Has($associatedDataType, $lookupAttr, $lookupValueAttr);
    }
}