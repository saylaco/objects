<?php

namespace Sayla\Objects\Contract\Attributes;

use Illuminate\Support\Str;

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
        return $this->lookupValueAttribute
            ?? ($this->lookupValueAttribute = $this->guessOwnerAttrPrefix() . 'Id');
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

    /**
     * @return string
     */
    protected function guessAssociatedAttrPrefix(): string
    {
        return Str::camel(Str::singular(class_basename($this->getAssociatedDataType())));
    }

    /**
     * @return string
     */
    protected function guessOwnerAttrPrefix(): string
    {
        return Str::camel(Str::singular(class_basename($this->getOwningObjectClass())));
    }
}