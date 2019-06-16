<?php

namespace Sayla\Objects\Contract\Attributes;

use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

interface Property extends Jsonable, JsonSerializable
{
    public function getName(): string;

    public function getValue();
}