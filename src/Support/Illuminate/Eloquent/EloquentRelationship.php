<?php

namespace Sayla\Objects\Support\Illuminate\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Sayla\Objects\Contract\Attributes\AssociationResolver;
use Sayla\Objects\Contract\Attributes\AssociationResolverTrait;
use Sayla\Objects\Contract\Attributes\AttributeResolverTrait;
use Sayla\Objects\Contract\Attributes\SupportsCallableResolverTrait;
use Sayla\Objects\DataObject;
use Sayla\Objects\DataType\DataTypeManager;

class EloquentRelationship implements AssociationResolver
{
    use AssociationResolverTrait;
    use SupportsCallableResolverTrait;
    use AttributeResolverTrait;

    /**
     * @var string
     */
    private $modelClass;
    /**
     * @var string
     */
    private $relationMethod;

    public function __construct(string $modelClass, string $associatedDataType)
    {
        $this->setAssociatedDataType($associatedDataType);
        $this->modelClass = $modelClass;
        $this->associatedDataType = $associatedDataType;
    }

    protected function getModel($atts = []): Model
    {
        return make_new_instance($this->modelClass, $atts);
    }

    /**
     * @param \Sayla\Objects\DataObject|null $owningObject
     * @return \Sayla\Objects\Support\Illuminate\Eloquent\EloquentObjectBuilder
     */
    protected function getObjectQueryBuilder(DataObject $owningObject = null): EloquentObjectBuilder
    {
        $relation = $this->getRelation($owningObject);
        $associatedDt = DataTypeManager::resolve()->get($this->associatedDataType);
        return new EloquentObjectBuilder($relation->getBaseQuery(), $associatedDt);
    }

    /**
     * @param \Sayla\Objects\DataObject $owningObject
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function getRelation(DataObject $owningObject): Relation
    {
        /** @var \Sayla\Objects\Support\Illuminate\Eloquent\EloquentLookup $lookup */
        $ownerDt = DataTypeManager::resolve()->get($this->owningObjectClass);
        $model = $owningObject ? $this->getModel($ownerDt->extract($owningObject)) : $this->getModel();
        /** @var \Illuminate\Database\Eloquent\Relations\Relation $relation */
        $relation = $model->{$this->getAttribute()}();
        return $relation;
    }

    public function isSingular(): bool
    {
        return false;
    }

    public function resolve(DataObject $owningObject)
    {
        $objectBuilder = $this->getObjectQueryBuilder($owningObject);
        return $objectBuilder->getObjects();
    }

    public function resolveMany($objects): array
    {
        return $this->resolveManyUsingSingleResolver($objects);
    }
}