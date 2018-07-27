<?php

namespace Sayla\Objects\Attribute\Resolver;

use Sayla\Objects\Contract\AttributeResolver;
use Sayla\Objects\DataObject;

class ResolverDelegate implements AttributeResolver
{
    /** @var string */
    protected $attributeName;
    /** @var string */
    protected $owningObjectClass;
    /**
     * @var \Sayla\Objects\Contract\AttributeResolver
     */
    protected $singleValueResolver;
    /**
     * @var \Sayla\Objects\Contract\AttributeResolver
     */
    protected $multipleValueResolver;

    public function __construct(AttributeResolver $singleValueResolver, AttributeResolver $multipleValueResolver)
    {
        $this->singleValueResolver = $singleValueResolver;
        $this->multipleValueResolver = $multipleValueResolver;
    }

    public function getAttribute(): string
    {
        return $this->attributeName;
    }

    public function getOwningObjectClass(): string
    {
        return $this->owningObjectClass;
    }

    /**
     * @param \Sayla\Objects\DataObject $owningObject
     * @return mixed
     */
    public function resolve(DataObject $owningObject)
    {
        return $this->singleValueResolver->resolve($owningObject);
    }

    public function resolveMany($objects): array
    {
        return $this->multipleValueResolver->resolveMany($objects);
    }

    public function setOwnerAttributeName(string $name)
    {
        $this->attributeName = $name;
    }

    public function setOwnerObjectClass(string $objectClass)
    {
        $this->owningObjectClass = $objectClass;
    }
}