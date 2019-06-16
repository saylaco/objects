<?php

namespace Sayla\Objects\Contract\DataObject;

use Illuminate\Contracts\Support\Responsable;

interface ResponsableObject extends Responsable
{

    public static function getResponseResolvableAttributes(): array;

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Sayla\Objects\AttributableObject
     */
    public function getResponseObject($request);
}