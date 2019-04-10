<?php

namespace Sayla\Objects\Support\Illuminate;
/**
 * @mixin \Sayla\Objects\StorableTrait
 * @method static EloquentStore getStore
 */
trait EloquentObjectTrait
{
    public static function all()
    {
        return self::dataType()->hydrateMany(static::query()->toBase()->get());
    }

    public static function find($id)
    {
        $model = static::query()->toBase()->find($id);
        return $model
            ? self::dataType()->hydrate($model)
            : null;
    }

    public static function findOrFail($id)
    {
        return self::dataType()->hydrate(static::query()->toBase()->findOrFail($id));
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