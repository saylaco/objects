<?php

namespace Sayla\Objects\Contract;

use Sayla\Objects\Builder\DataTypeConfig;

interface DataTypeConfigCache
{
    public function addConfig(DataTypeConfig $builder);

    public function flush();

    /**
     * A list of object with two properties:
     *
     * @return array[]
     */
    public function getAllDataTypeConfigs(): iterable;

}