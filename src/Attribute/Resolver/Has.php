<?php

namespace Sayla\Objects\Attribute\Resolver;

use Sayla\Objects\Contract\Attributes\AssociationResolver;
use Sayla\Objects\Contract\Attributes\AssociationResolverTrait;
use Sayla\Objects\Contract\Attributes\AttributeResolverTrait;
use Sayla\Objects\Contract\Attributes\SupportsCallableResolverTrait;
use Sayla\Objects\DataObject;
use Sayla\Objects\DataType\DataTypeManager;

class Has implements AssociationResolver
{
    use AssociationResolverTrait;
    use SupportsCallableResolverTrait;
    use AttributeResolverTrait;
    private $isSingular = true;

    public function __construct(string $associatedDataType, string $lookupAttr = null, string $lookupValueAttr = null)
    {
        $this->setAssociatedDataType($associatedDataType);
        if ($lookupAttr) {
            $this->setLookupAttribute($lookupAttr);
        }
        $this->lookupValueAttribute = $lookupValueAttr;
    }

    public function isSingular(): bool
    {
        return $this->isSingular;
    }

    public function multiple()
    {
        $this->isSingular = false;
        return true;
    }

    /**
     * @param \Sayla\Objects\DataObject $owningObject
     * @return mixed|\Sayla\Objects\Contract\IDataObject|\Sayla\Objects\ObjectCollection
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Sayla\Exception\Error
     */
    public function resolve(DataObject $owningObject)
    {
        if ($this->callable) {
            return $this->runCallable($owningObject);
        }
        if ($this->isSingular()) {
            return DataTypeManager::resolve()->getObjectLookup($this->associatedDataType)
                ->findBy($this->getLookupAttribute(), $owningObject[$this->getLookupValueAttribute()]);
        }
        return DataTypeManager::resolve()->getObjectLookup($this->associatedDataType)
            ->getWhere($this->getLookupAttribute(), $owningObject[$this->getLookupValueAttribute()]);
    }

    public function resolveMany($objects): array
    {
        return $this->resolveManyUsingSingleResolver($objects);
    }

    public function single()
    {
        $this->isSingular = true;
        return true;
    }
}