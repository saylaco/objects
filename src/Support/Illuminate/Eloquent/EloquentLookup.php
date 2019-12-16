<?php

namespace Sayla\Objects\Support\Illuminate\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Sayla\Objects\Contract\Stores\Lookup;
use Sayla\Objects\DataType\DataType;

class EloquentLookup implements Lookup
{
    /**
     * @var \Sayla\Objects\DataType\DataType
     */
    private $dataType;
    private $findKeyCallback = null;
    /**
     * @var string
     */
    private $keyName;
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    private $model;
    /** @var callable */
    private $queryPreparer;
    /**
     * @var string
     */
    private $tableName;

    /**
     * TableLookup constructor.
     * @param string $table
     * @param string $keyName
     */
    public function __construct(DataType $dataType, Model $model)
    {
        $this->keyName = $model->getKeyName();
        $this->dataType = $dataType;
        $this->tableName = $model->getTable();
        $this->model = $model;
    }

    /**
     * @return \Sayla\Objects\ObjectCollection
     */
    public function all()
    {
        return $this->newQuery()->get();
    }

    public function exists(?string $key): bool
    {
        return filled($key) && $this->newQuery()->where($this->determineKeyName($key), $key)->exists();
    }

    public function find($key)
    {
        return $this->findBy($this->determineKeyName($key), $key);
    }

    /**
     * @param $attribute
     * @param $key
     * @return \Sayla\Objects\Contract\IDataObject
     */
    public function findBy($attribute, $value)
    {
        return $this->newQuery()->where($attribute, $value)->first();
    }

    public function findOrFail($key)
    {
        return $this->newQuery()->where($this->determineKeyName($key), $key)->firstOrFail();
    }

    /**
     * @param $key
     * @return \Sayla\Objects\Support\Illuminate\Eloquent\EloquentObjectBuilder
     */
    public function findWhere($key)
    {
        return $this->newQuery()->where($this->determineKeyName($key), $key);
    }

    /**
     * @return string
     */
    public function getKeyName(): string
    {
        return $this->keyName;
    }

    public function getWhere($attribute, $value = null)
    {
        $query = is_string($attribute) || func_num_args() > 1
            ? $this->newQuery()->where($attribute, $value)
            : $this->newQuery()->where($attribute);
        return $query->get();
    }

    /**
     * @return \Sayla\Objects\Support\Illuminate\Eloquent\EloquentObjectBuilder
     */
    public function newQuery(): EloquentObjectBuilder
    {
        $builder = new EloquentObjectBuilder(
            $this->model->newModelQuery()->toBase(),
            $this->dataType
        );
        $builder->setModel($this->model);
        if ($this->queryPreparer) {
            call_user_func($this->queryPreparer, $builder);
        }
        return $builder;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel(): \Illuminate\Database\Eloquent\Model
    {
        return $this->model;
    }

    public function setFindKeyPicker(callable $callback)
    {
        $this->findKeyCallback = $callback;
        return $this;
    }

    /**
     * @param callable $queryPreparer
     * @return EloquentLookup
     */
    public function setQueryPreparer(callable $queryPreparer)
    {
        $this->queryPreparer = $queryPreparer;
        return $this;
    }

    /**
     * @param $attribute
     * @param null $comparator
     * @param null $value
     * @return \Sayla\Objects\Support\Illuminate\Eloquent\EloquentObjectBuilder
     */
    public function where($attribute, $comparator = null, $value = null): EloquentObjectBuilder
    {
        return is_string($attribute) || func_num_args() > 1
            ? $this->newQuery()->where($attribute, $comparator, $value)
            : $this->newQuery()->where($attribute);
    }

    private function determineKeyName($key): string
    {
        $keyName = null;
        if ($this->findKeyCallback) {
            $keyName = call_user_func($this->findKeyCallback, $key);
        }
        return $this->model->getTable() . '.' . ($keyName ?? $this->keyName);
    }
}