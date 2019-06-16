<?php

namespace Sayla\Objects\Contract\Stores;
/**
 * @method string toStoreString
 * @method iterable create($object)
 * @method iterable delete($object)
 * @method iterable update($object)
 * @method bool exists($identifier)
 */
interface ObjectStore
{
}