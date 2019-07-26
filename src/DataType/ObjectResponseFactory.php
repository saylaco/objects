<?php

namespace Sayla\Objects\DataType;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Sayla\Objects\AttributableObject;
use Sayla\Objects\Contract\Attributes\Attributable;
use Sayla\Objects\Contract\DataType\ObjectResponseFactory as IObjectResponseFactory;
use Sayla\Objects\Contract\IDataObject;
use Sayla\Objects\DataObject;
use Sayla\Objects\ObjectCollection;

class ObjectResponseFactory implements IObjectResponseFactory
{
    private $attributes = [];

    /**
     * @param string[] $attributes
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @param string[] $attributes
     */
    public function addAttributes(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Sayla\Objects\Contract\IDataObject $object
     * @return \Sayla\Objects\Contract\Attributes\Attributable
     */
    public function getObjectAttributes(Request $request, IDataObject $object): Attributable
    {
        return $this->resolveObjectAttributes($request, $object, $this->attributes);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Sayla\Objects\ObjectCollection $param
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function makeCollectionResponse(Request $request, ObjectCollection $collection)
    {
        $simpleCollection = collect();
        $collection->each(function ($object) use ($request, $simpleCollection) {
            $simpleCollection[] = $this->getObjectAttributes($request, $object);
        });
        return new JsonResponse($simpleCollection);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Sayla\Objects\Contract\IDataObject $object
     * @return \Illuminate\Http\JsonResponse
     */
    public function makeObjectResponse(Request $request, IDataObject $object)
    {
        return new JsonResponse($this->getObjectAttributes($request, $object));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Sayla\Objects\Contract\IDataObject $obj
     * @param array $resolvable
     * @return \Sayla\Objects\Contract\Attributes\Attributable
     */
    protected function resolveObjectAttributes(Request $request, IDataObject $obj, array $resolvable)
    {
        $resolvableAttributes = Arr::wrap($request->query('resolve'));
        if (!empty($resolvable)) {
            $resolvableAttributes = array_merge($resolvableAttributes, $resolvable);
        }
        if (filled($resolvableAttributes)) {
            $obj->resolve(...$resolvableAttributes);
        }
        $visibleObject = $this->toVisibleObject($obj);
        if ($obj instanceof DataObject) {
            $visibleObject->fill($obj->runGetters(...$resolvableAttributes));
        }
        return $visibleObject;
    }

    /**
     * @return \Sayla\Objects\AttributableObject
     */
    public function toVisibleObject(IDataObject $ob, DataTypeDescriptor $descriptor = null)
    {
        $map = $this->getVisibleValueMap($ob, $descriptor ?? $ob::descriptor());
        foreach ($map['resolvable'] as $k => $v) {
            if ($v instanceof IDataObject) {
//                $v::descriptor()->getResponseFactory()->makeObjectResponse($request, $object)
                $map['values'][$k] = $this->toVisibleObject($v, $v::descriptor());
            }
        }
        return new AttributableObject($map['values']);
    }

    /**
     * @param \Sayla\Objects\Contract\IDataObject $obj
     * @param \Sayla\Objects\DataType\DataTypeDescriptor $descriptor
     * @return array
     */
    private function getVisibleValueMap(IDataObject $obj, DataTypeDescriptor $descriptor): array
    {
        $values = Arr::except($obj->toArray(), $descriptor->getHidden());
        $resolvable = Arr::only($values, $descriptor->getResolvable());
        return compact('values', 'resolvable');
    }
}

