<?php

namespace Sayla\Objects\Contract\DataType;

use Illuminate\Http\Request;
use Sayla\Objects\Contract\Attributes\Attributable;
use Sayla\Objects\Contract\IDataObject;
use Sayla\Objects\ObjectCollection;

interface ObjectResponseFactory
{
    /**
     * @param string[] $attributes
     */
    public function addAttributes(array $attributes);

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Sayla\Objects\Contract\IDataObject $object
     * @return \Sayla\Objects\Contract\Attributes\Attributable
     */
    public function getObjectAttributes(Request $request, IDataObject $object): Attributable;

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