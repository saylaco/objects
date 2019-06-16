<?php

namespace Sayla\Objects\Contract\DataObject;

interface Lookable
{
    /**
     * @return \Sayla\Objects\Stores\FileStore\ObjectCollectionLookup
     */
    public static function lookup();
}