<?php

namespace Sayla\Objects\Support\Illuminate\Eloquent;

/**
 * @mixin \Sayla\Objects\Contract\DataObject\StorableObjectTrait
 * @method static EloquentStore getStore
 */
trait AsEloquentObject
{
    /**
     * @return static[]
     * @throws \Sayla\Objects\Contract\Exception\HydrationError
     */
    public static function all()
    {
        return self::dataType()->hydrateMany(static::query()->toBase()->get());
    }

    /**
     * @param $id
     * @return static
     * @throws \Sayla\Objects\Contract\Exception\HydrationError
     */
    public static function find($id)
    {
        $model = static::query()->toBase()->find($id);
        return $model
            ? self::dataType()->hydrate($model)
            : null;
    }

    public static function findAll(array $filter)
    {
        $query = static::query();
        $renamedData = self::dataType()->extract($filter);
        $query->where($renamedData);
        $results = $query->toBase()->get();
        return self::dataType()->hydrateMany($results);
    }

    /**
     * @param $id
     * @return static
     * @throws \Sayla\Objects\Contract\Exception\HydrationError
     */
    public static function findOrFail($id)
    {
        return self::dataType()->hydrate(static::query()->findOrFail($id));
    }

    public static function query()
    {
        return static::getStore()->getModel()->newQuery();
    }

    protected function determineExistence(): bool
    {
        return $this->id > 0;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->id;
    }
}