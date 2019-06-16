<?php

namespace Sayla\Objects\Contract\Attributes;

trait AssociationResolverTrait
{
    /** @var string */
    protected $associatedDataType;
    /** @var string */
    protected $lookupAttribute;
    private $lookupValueAttribute;

    public function getAssociatedDataType(): string
    {
        return $this->associatedDataType;
    }

    public function setAssociatedDataType(string $dataType)
    {
        $this->associatedDataType = $dataType;
    }

    public function getLookupAttribute(): string
    {
        return $this->lookupAttribute ?? ($this->lookupAttribute = 'id');
    }

    public function setLookupAttribute(string $attributeName)
    {
        $this->lookupAttribute = $attributeName;
    }

    /**
     * @return mixed
     */
    public function getLookupValueAttribute(): string
    {
        return $this->lookupValueAttribute ?? ($this->lookupValueAttribute = $this->getAttribute() . 'Id');
    }

    /**
     * @param string $lookupValueAttribute
     * @return $this
     */
    public function setLookupValueAttribute(string $lookupValueAttribute)
    {
        $this->lookupValueAttribute = $lookupValueAttribute;
        return $this;
    }
}