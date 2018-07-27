<?php

namespace Sayla\Objects\Contract;

interface AssociationResolver extends AttributeResolver
{
    public function getAssociatedAttribute(): string;

    public function getAssociatedObjectClass(): string;

    public function setAssociatedAttributeName(string $attributeName);

    public function setAssociatedObjectClass(string $objectClass);
}