<?php

namespace Sayla\Objects\Support\Illuminate;

use Illuminate\Http\JsonResponse;

/**
 * @mixin \Sayla\Objects\DataObject
 * @mixin \Illuminate\Contracts\Support\Responsable
 */
trait ResponsableObjectTrait
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Sayla\Objects\AttributableObject
     */
    public function getResponseObject($request)
    {
       return ObjectsResponse::resolveObjectVisibleAttributesFromRequest($this, $request);
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function toResponse($request)
    {
        $obj = $this->getResponseObject($request);
        return new JsonResponse($obj->toArray());
    }
}