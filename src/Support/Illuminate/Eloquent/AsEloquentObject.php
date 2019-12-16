<?php

namespace Sayla\Objects\Support\Illuminate\Eloquent;

use Sayla\Objects\Contract\DataObject\LookableTrait;

/**
 * @mixin \Sayla\Objects\Contract\DataObject\StorableObjectTrait
 * @method static EloquentStore getStore
 * @method static EloquentLookup lookup
 */
trait AsEloquentObject
{
    use LookableTrait;

    public static function findAll(array $filter)
    {
        $data = static::dataType()->extract($filter);
        return static::lookup()->getWhere($data);
    }

    public static function findOrFail($id)
    {
        return static::lookup()->findOrFail($id);
    }

    /**
     * @return \Sayla\Objects\Support\Illuminate\Eloquent\EloquentObjectBuilder
     */
    public static function query()
    {
        return static::lookup()->newQuery();
    }

    public static function transaction(callable $callback, $attempts = 1)
    {
        return static::lookup()->getModel()->getConnection()->transaction($callback, $attempts);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function model()
    {
        $model = static::lookup()->getModel();
        return $model->newInstance([
            $model->getKeyName() => $this->getKey()
        ]);
    }
}