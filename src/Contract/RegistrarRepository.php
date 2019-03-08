<?php

namespace Sayla\Objects\Contract;

use Sayla\Objects\Builder\Builder;

interface RegistrarRepository
{
    /**
     * A list of object with two properties:
     *   $info->dataTypeClass - class of datatype
     *   $info->name - name of object
     *   $info->store - store options
     *
     * @return iterable|object[]
     */
    public function getObjects():iterable;

    public function getOptions(string $objectName): array;

    public function addObject(string $objectName, Builder $builder);

}