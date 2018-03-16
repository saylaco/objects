<?php

namespace Sayla\Objects\Resolvers;

abstract class AttributeResolver
{

    protected $attributeName;
    protected $owningObjectClass;

    public function getAttributeName(): string
    {
        return $this->attributeName;
    }

    /**
     * @return mixed
     */
    public function getOwningObjectClass(): string
    {
        return $this->owningObjectClass;
    }

    /**
     * @param \Sayla\Objects\ObjectCollection|\Sayla\Objects\DataObject[] $objects
     * @param callable $builder
     * @return mixed[]
     */
    public function resolveMany($objects, callable $builder): array
    {
        $values = [];
        foreach ($objects as $i => $object) {
            $values[$i] = $this->resolve($object) ?? $builder($object);
        }
        return $values;
    }

    /**
     * @param \Sayla\Objects\DataObject $owningObject
     * @return mixed
     */
    abstract public function resolve($owningObject);

    public function setOwnerAttributeName(string $name)
    {
        $this->attributeName = $name;
    }

    public function setOwnerObjectClass(string $objectClass)
    {
        $this->owningObjectClass = $objectClass;
    }
}