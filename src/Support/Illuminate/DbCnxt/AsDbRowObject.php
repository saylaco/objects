<?php

namespace Sayla\Objects\Support\Illuminate\DbCnxt;

use Sayla\Objects\Contract\DataObject\LookableTrait;

/**
 * @mixin \Sayla\Objects\Contract\DataObject\StorableObjectTrait
 * @method static DbTableStore getStore
 * @method static TableLookup lookup
 */
trait AsDbRowObject
{
    use LookableTrait;
    
    public static function findAll(array $filter)
    {
        $lookup = static::lookup();
        $data = static::dataType()->extract($filter);
        $results = $lookup->newQuery()->where($data)->get();
        return static::dataType()->hydrateMany($results);
    }

    public static function findOrFail($id)
    {
        return static::lookup()->findOrFail($id);
    }

}