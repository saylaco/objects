<?php

namespace Sayla\Objects\Contract;

use Sayla\Objects\Builder\DataTypeConfig;

interface RegistrarRepository
{
    public function addBuilder(DataTypeConfig $builder);

    public function flush();

    /**
     * A list of object with two properties:
     *
     * @return array[]
     */
    public function getAllOptions(): iterable;

}