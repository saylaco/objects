<?php

namespace Sayla\Objects\Contract;

use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

interface Property extends Jsonable, JsonSerializable
{
    public function getName(): string;

    public function getValue();
}