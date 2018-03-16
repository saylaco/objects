<?php

namespace Sayla\Objects\Strategies\Illuminate;

use Illuminate\Database\Eloquent\Model;

/**
 * @method array createModel
 * @method array updateModel
 * @method array deleteModel
 */
abstract class EloquentStrategy extends ModelStrategy
{
    /** @var Model */
    protected $model;

    /**
     * @param $key
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    protected function findModel($key)
    {
        return $this->newQuery()->findOrFail($key);
    }

    /**
     * @return \Illuminate\Database\Connection
     */
    protected function getConnection(): \Illuminate\Database\Connection
    {
        return $this->model->getConnection();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function newQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->model->newQuery();
    }

    public function toStoreString(): string
    {
        return 'Eloquent[' . get_class($this->model) . ']';
    }
}