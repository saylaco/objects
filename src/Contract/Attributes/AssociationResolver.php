<?php

namespace Sayla\Objects\Contract\Attributes;

interface AssociationResolver extends AttributeResolver
{
    public function getAssociatedDataType(): string;

    public function getLookupAttribute(): string;

    public function getLookupValueAttribute(): string;

    public function isSingular(): bool;

    public function setAssociatedDataType(string $dataType);

    public function setLookupAttribute(string $attributeName);

    public function setLookupValueAttribute(string $lookupValueAttribute);
}