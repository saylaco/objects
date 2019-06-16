<?php

namespace Sayla\Objects\Contract\DataObject;


use Sayla\Objects\DataType\DataTypeManager;

trait SupportsDataTypeManagerTrait
{
    private static $dataTypeManager;

    public static function getDataTypeManager(): DataTypeManager
    {
        return self::$dataTypeManager ?? DataTypeManager::resolve();
    }

    public static function setDataTypeManager(DataTypeManager $dataTypeManager)
    {
        self::$dataTypeManager = $dataTypeManager;
    }
}