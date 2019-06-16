<?php

namespace Sayla\Objects\Contract\DataObject;

interface ProvidesResolvers
{
    /**
     * @param \Sayla\Objects\Attribute\AttributeResolverFactory $factory
     * @return array<string, \Sayla\Objects\Contract\Attributes\AttributeResolver>
     */
    public static function getResolvers($factory): array;
}