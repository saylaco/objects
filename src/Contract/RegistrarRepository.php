<?php

namespace Sayla\Objects\Contract;

use Sayla\Objects\Builder\Builder;

interface RegistrarRepository
{
    public function addBuilder(Builder $builder);

    public function flush();

    /**
     * A list of object with two properties:
     *
     * @return array[]
     */
    public function getBuilders(): iterable;

}