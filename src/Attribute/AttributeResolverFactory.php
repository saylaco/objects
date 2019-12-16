<?php

namespace Sayla\Objects\Attribute;

use Illuminate\Support\Traits\Macroable;
use Sayla\Objects\Attribute\Resolver\EnumResolver;
use Sayla\Objects\Attribute\Resolver\Has;
use Sayla\Objects\Attribute\Resolver\HasMany;

class AttributeResolverFactory
{
    use Macroable;

    public function enum(string $enumClass)
    {
        return new EnumResolver($enumClass);
    }

    public function hasMany(string $associatedDataType, string $lookupValueAttr = null, string $lookupAttr = null)
    {
        return (new HasMany($associatedDataType, $lookupAttr, $lookupValueAttr));
    }

    public function hasOne(string $associatedDataType, string $lookupValueAttr = null, string $lookupAttr = null)
    {
        return new Has($associatedDataType, $lookupAttr, $lookupValueAttr);
    }
}