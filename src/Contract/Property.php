<?php

namespace Sayla\Objects\Contract;

use Illuminate\Contracts\Support\Jsonable;

interface Property extends Jsonable, \JsonSerializable
{
    public function getName(): string;

    public function getTypeHandle(): string;

    public function getValue();
}