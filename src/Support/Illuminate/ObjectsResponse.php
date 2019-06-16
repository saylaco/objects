<?php

namespace Sayla\Objects\Support\Illuminate;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sayla\Objects\Contract\DataObject\ResponsableObject;
use Sayla\Objects\DataObject;
use Sayla\Objects\ObjectCollection;

class ObjectsResponse extends JsonResponse
{
    private $resolvableAttributes;

    /**
     * DataObjectResponse constructor.
     * @param \Sayla\Objects\ObjectCollection $items
     * @param array $resolvableAttributes
     * @param int $status
     * @param array $headers
     * @param int $options
     * @throws \Sayla\Objects\Contract\Exception\AttributeResolverNotFound
     */
    public function __construct(ObjectCollection $items, array $resolvableAttributes, $status = 200, $headers = [],
                                $options = 0)
    {
        $this->resolvableAttributes = $resolvableAttributes;
        $obj = $items->first();
        if ($obj instanceof ResponsableObject) {
            $this->resolvableAttributes = array_merge(
                $this->resolvableAttributes,
                $obj::getResponseResolvableAttributes()
            );
        }
        parent::__construct($items, $status, $headers, $options);
    }

    /**
     * @param \Sayla\Objects\ObjectCollection $collection
     * @param \Illuminate\Http\Request $request
     * @return \Sayla\Objects\ObjectsResponse
     */
    public static function makeFromRequest(ObjectCollection $collection, Request $request)
    {
        return new static($collection, array_wrap($request->query('resolve')));
    }

    /**
     * @param \Sayla\Objects\ObjectCollection $collection
     * @param $request
     * @return \Sayla\Objects\AttributableObject[]
     * @throws \Sayla\Objects\Contract\Exception\AttributeResolverNotFound
     */
    public static function resolveObjectVisibleAttributesFromRequest(DataObject $obj, Request $request)
    {
        $resolvableAttributes = array_wrap($request->query('resolve'));
        if ($obj instanceof ResponsableObject) {
            $resolvableAttributes = array_merge(
                $resolvableAttributes, $obj::getResponseResolvableAttributes()
            );
        }
        if (filled($resolvableAttributes)) {
            $obj->resolve(...$resolvableAttributes);
        }
        $visibleObject = $obj->toVisibleObject();
        $visibleObject->fill($obj->runGetters(...$resolvableAttributes));
        return $visibleObject;
    }

    /**
     * @param \Sayla\Objects\ObjectCollection $collection
     * @param $request
     * @return \Sayla\Objects\AttributableObject[]|\Illuminate\Support\Collection
     * @throws \Sayla\Objects\Contract\Exception\AttributeResolverNotFound
     */
    public static function resolveVisibleAttributes(ObjectCollection $collection, array $resolvableAttributes = [])
    {
        if (filled($resolvableAttributes)) {
            $collection->resolve(...$resolvableAttributes);
        }
        /** @var \Illuminate\Support\Collection $visibleObjects */
        return $collection->map(function (DataObject $obj) use ($resolvableAttributes) {
            $visibleObject = $obj->toVisibleObject();
            $visibleObject->fill($obj->runGetters(...$resolvableAttributes));
            return $visibleObject;
        });
    }

    public function setData($data = [])
    {
        return parent::setData(self::resolveVisibleAttributes($data, $this->resolvableAttributes));
    }
}