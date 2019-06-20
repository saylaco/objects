<?php

namespace Sayla\Objects\Support\Illuminate\DbCnxt;

use Sayla\Objects\Contract\DataObject\LookableTrait;

/**
 * @mixin \Sayla\Objects\Contract\DataObject\StorableObjectTrait
 * @method static DbTableStore getStore
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

    public static function lookup()
    {
        return static::getStore()->lookup();
    }

    protected function determineExistence(): bool
    {
        return !empty($this->getKey());
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->getAttributeValue(self::getStore()->getKeyName());
    }
}