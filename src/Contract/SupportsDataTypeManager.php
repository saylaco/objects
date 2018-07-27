<?php

namespace Sayla\Objects\Contract;


use Sayla\Objects\DataType\DataTypeManager;

interface SupportsDataTypeManager
{

    public static function getDataTypeManager(): DataTypeManager;

    public static function setDataTypeManager(DataTypeManager $dataTypeManager);
}