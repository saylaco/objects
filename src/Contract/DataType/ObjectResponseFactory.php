<?php

namespace Sayla\Objects\Contract\DataType;

use Illuminate\Http\Request;
use Sayla\Objects\ObjectCollection;
use Sayla\Objects\Contract\IDataObject;

interface ObjectResponseFactory
{
    /**
     * @param string[] $attributes
     */
    public function addAttributes(array $attributes);

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Sayla\Objects\ObjectCollection $collection
     * @return \Illuminate\Http\Response
     */
    public function makeCollectionResponse(Request $request, ObjectCollection $collection);

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Sayla\Objects\Contract\IDataObject $object
     * @return \Illuminate\Http\JsonResponse
     */
    public function makeObjectResponse(Request $request, IDataObject $object);
}