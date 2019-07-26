<?php

namespace Sayla\Objects\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\HigherOrderCollectionProxy;
use Sayla\Objects\Contract\IDataObject;
use Sayla\Objects\DataObject;
use Sayla\Objects\ObjectCollection;

class ObjectCollectionProxy extends HigherOrderCollectionProxy
{

    /**
     * Create a new proxy instance.
     *
     * @param \Illuminate\Support\Collection $collection
     * @param string $method
     * @return void
     */
    public function __construct(ObjectCollection $collection, $method)
    {
        $base = new Collection();
        $collection->each(function ($obj, $i) use ($base) {
            $base[$i] = $obj;
        });
        parent::__construct($base, $method);
    }

    /**
     * Proxy a method call onto the collection items.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->toResult(parent::__call($method, $parameters));
    }

    /**
     * Proxy accessing an attribute onto the collection items.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->toResult(parent::__get($key));
    }

    /**
     * @param $results
     * @return \Sayla\Objects\ObjectCollection
     */
    private function toResult($results)
    {
        if ($results instanceof Collection) {
            $first = $results->first();
            if ($first instanceof DataObject) {
                return $first::newCollection()->pushItems($results);
            } elseif ($first instanceof IDataObject) {
                return ObjectCollection::makeFor($first::dataTypeName())->pushItems($results);
            }
        }
        return $results;
    }
}
