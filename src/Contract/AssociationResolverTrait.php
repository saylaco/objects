<?php

namespace Sayla\Objects\Contract;

trait AssociationResolverTrait
{

    /** @var string */
    protected $associatedAttribute;
    /** @var string */
    protected $associatedObjectClass;

    /**
     * @return string
     */
    public function getAssociatedAttribute(): string
    {
        return $this->associatedAttribute;
    }

    /**
     * @return string
     */
    public function getAssociatedObjectClass(): string
    {
        return $this->associatedObjectClass;
    }

    /**
     * @param string $associatedObjectClass
     * @return AssociationResolverTrait
     */
    public function setAssociatedObjectClass(string $associatedObjectClass)
    {
        $this->associatedObjectClass = $associatedObjectClass;
        return $this;
    }

    /**
     * @param string $associatedAttribute
     * @return AssociationResolverTrait
     */
    public function setAssociatedAttributeName(string $associatedAttribute)
    {
        $this->associatedAttribute = $associatedAttribute;
        return $this;
    }


}