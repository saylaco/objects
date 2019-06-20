<?php

namespace Sayla\Objects\Support\Illuminate\DbCnxt;

use Illuminate\Database\Query\Builder;
use Sayla\Exception\RecordNotFound;
use Sayla\Objects\Contract\Stores\Lookup;
use Sayla\Objects\DataType\DataType;

class TableLookup implements Lookup
{
    /**
     * @var \Illuminate\Database\Query\Builder
     */
    private $builder;
    /**
     * @var \Sayla\Objects\DataType\DataType
     */
    private $dataType;
    /**
     * @var string
     */
    private $keyName;
    /**
     * @var string
     */
    private $tableName;

    /**
     * TableLookup constructor.
     * @param string $table
     * @param string $keyName
     */
    public function __construct(DataType $dataType, Builder $builder, string $tableName, string $keyName)
    {
        $this->builder = $builder;
        $this->keyName = $keyName;
        $this->dataType = $dataType;
        $this->tableName = $tableName;
    }

    /**
     * @return \Sayla\Objects\ObjectCollection
     */
    public function all()
    {
        $results = $this->newQuery()->get();
        return $this->dataType->hydrateMany($results);
    }

    public function find($key)
    {
        return $this->findBy($this->keyName, $key);
    }

    /**
     * @param $attribute
     * @param $key
     * @return \Sayla\Objects\Contract\IDataObject
     */
    public function findBy($attribute, $value)
    {
        $result = $this->newQuery()->where($attribute, $value)->first();
        return $result ? $this->dataType->hydrate($result) : null;
    }

    public function findOrFail($key)
    {
        $result = $this->find($key);
        if ($result) {
            throw new RecordNotFound($this->tableName . '#' . $key);
        }
        return $result;
    }

    public function exists(string $key): bool
    {
        return $this->newQuery()->where($this->keyName, $key)->exists();
    }
    public function getWhere($attribute, $value)
    {
        $results = $this->newQuery()->where($attribute, $value)->get();
        return $this->dataType->hydrateMany($results);
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function newQuery(): Builder
    {
        return $this->builder->newQuery()->from($this->tableName);
    }
}