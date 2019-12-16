<?php

namespace Sayla\Objects\Support\Illuminate\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Sayla\Objects\DataType\DataType;

/**
 * Class EloquentObjectBuilder
 * @mixin \Illuminate\Database\Query\Builder
 */
class EloquentObjectBuilder extends Builder
{
    /**
     * @var \Sayla\Objects\DataType\DataType
     */
    protected $dataType;

    /**
     * Create a new Eloquent query builder instance.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return void
     */
    public function __construct(QueryBuilder $query, DataType $dataType)
    {
        parent::__construct($query);
        $this->dataType = $dataType;
    }


    public function get($columns = ['*'])
    {
        $builder = $this->applyScopes();
        if (count($this->eagerLoad) === 0) {
            return $this->getObjects($columns);
        }
        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded, which will solve the
        // n+1 query issue for the developers to avoid running a lot of queries.
        if (count($models = $builder->getModels($columns)) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }
        return $this->dataType->hydrateMany($models);
    }

    /**
     * @param array $columns
     * @return \Sayla\Objects\DataType\DataType[]|\Sayla\Objects\ObjectCollection
     * @throws \Sayla\Objects\Contract\Exception\HydrationError
     */
    public function getObjects($columns = ['*'])
    {
        $builder = $this->query;
//        foreach ($builder->wheres as $where)
//        logger($builder->wheres);
        return $this->dataType->hydrateMany($builder->get($columns));
    }

    /**
     * @param \Sayla\Objects\DataType\DataType $dataType
     * @return EloquentObjectBuilder
     */
    public function useDataType(DataType $dataType): EloquentObjectBuilder
    {
        $this->dataType = $dataType;
        return $this;
    }


}