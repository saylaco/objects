<?php

namespace Sayla\Objects\Attribute;

final class DefaultPropertyTypeSet extends PropertyTypeSet
{
    public function __construct()
    {
        parent::__construct([
            new Property\ResolverPropertyType(),
            new Property\AccessPropertyType(),
            new Property\DefaultPropertyType(),
            new Property\VisibilityPropertyType(),
            new Property\TransformationPropertyType(),
            new Property\ValidationPropertyType(),
            new Property\MapPropertyType(),
        ]);
    }
}