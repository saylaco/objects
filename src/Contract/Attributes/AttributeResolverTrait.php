<?php

namespace Sayla\Objects\Contract\Attributes;

trait AttributeResolverTrait
{

    protected $attributeName;
    protected $owningObjectClass;

    public function getAttribute(): string
    {
        return $this->attributeName;
    }

    public function getOwningObjectClass(): string
    {
        return $this->owningObjectClass;
    }

    public function setOwnerAttributeName(string $name)
    {
        $this->attributeName = $name;
    }

    public function setOwnerObjectClass(string $objectClass)
    {
        $this->owningObjectClass = $objectClass;
    }

    private function resolveManyUsingSingleResolver($objects): array
    {
        $values = [];
        foreach ($objects as $i => $object) {
            $values[$i] = $this->resolve($object);
        }
        return $values;
    }
}