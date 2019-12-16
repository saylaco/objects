<?php

namespace Sayla\Objects\Attribute\Resolver;

use Sayla\Objects\Contract\Attributes\AssociationResolver;
use Sayla\Objects\Contract\Attributes\AssociationResolverTrait;
use Sayla\Objects\Contract\Attributes\AttributeResolverTrait;
use Sayla\Objects\Contract\Attributes\SupportsCallableResolverTrait;
use Sayla\Objects\DataObject;
use Sayla\Objects\DataType\DataTypeManager;

class HasMany implements AssociationResolver
{
    use AssociationResolverTrait;
    use SupportsCallableResolverTrait;
    use AttributeResolverTrait;

    public function __construct(string $associatedDataType, string $lookupAttr = null, string $lookupValueAttr = null)
    {
        $this->setAssociatedDataType($associatedDataType);
        if ($lookupAttr) {
            $this->setLookupAttribute($lookupAttr);
        }
        if ($lookupValueAttr) {
            $this->setLookupValueAttribute($lookupValueAttr);
        }
    }

    public function getLookupAttribute(): string
    {
        return $this->lookupAttribute ?? ($this->lookupAttribute = $this->guessOwnerAttrPrefix() . 'Id');
    }

    /**
     * @return mixed
     */
    public function getLookupValueAttribute(): string
    {
        return $this->lookupValueAttribute ?? ($this->lookupValueAttribute = 'id');
    }

    public function isSingular(): bool
    {
        return false;
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
        return DataTypeManager::resolve()->getObjectLookup($this->associatedDataType)
            ->getWhere($this->getLookupAttribute(), $owningObject[$this->getLookupValueAttribute()]);
    }

    public function resolveMany($objects): array
    {
        return $this->resolveManyUsingSingleResolver($objects);
    }
}