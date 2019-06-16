<?php


namespace Sayla\Objects\Contract\Stores\FileStore;


interface FiltersRecords
{
    /**
     * @param array[] $rawObjects
     * @return mixed
     */
    public static function applyFilters($rawObjects): iterable;
}