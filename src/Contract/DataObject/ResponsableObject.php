<?php

namespace Sayla\Objects\Contract\DataObject;

use Illuminate\Contracts\Support\Responsable;

interface ResponsableObject extends Responsable
{

    public static function getResponseResolvableAttributes(): array;
}