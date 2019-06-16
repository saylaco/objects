<?php

namespace Sayla\Objects\Stores\FileStore;

use Sayla\Objects\Contract\Stores\Lookup;
use Sayla\Objects\DataType\DataTypeManager;
use Sayla\Objects\ObjectCollection;

class ObjectCollectionLookup implements Lookup
{
    /** @var string */
    protected $dataType;
    /** @var string */
    protected $keyAttribute;
    /** @var string|ObjectCollection */
    protected $objectCollectionClass = ObjectCollection::class;
    /**
     * @var iterable
     */
    private $records;

    /**
     * ObjectCollectionLookup constructor.
     * @param string $dataType
     * @param string $keyAttribute
     * @param iterable $records
     */
    public function __construct(string $dataType, string $keyAttribute, iterable $records)
    {
        $this->dataType = $dataType;
        $this->keyAttribute = $keyAttribute;
        $this->records = $records;
    }

    /**
     * @return \Sayla\Objects\ObjectCollection|\Sayla\Objects\Contract\DataObject\StorableObjectTrait[]
     */
    public function all()
    {
        $objectCollection = $this->objectCollectionClass::makeObjectCollection(
            $this->dataType,
            false,
            true,
            $this->keyAttribute
        );
        $objectCollection->makeObjects($this->records);
        return $objectCollection;
    }

    /**
     * @param $key
     * @return \Sayla\Objects\Contract\DataObject\StorableObjectTrait
     */
    public function find($key)
    {
        if (isset($this->records[$key])) {
            return DataTypeManager::resolve()->get($this->dataType)->hydrate($this->records[$key]);
        }
        return $this->all()->firstWhere($this->keyAttribute, '=', $key);
    }

    /**
     * @param $attribute
     * @param $key
     * @return \Sayla\Objects\Contract\DataObject\StorableObjectTrait
     */
    public function findBy($attribute, $value)
    {
        return $this->all()->firstWhere($attribute, '=', $value);
    }

    /**
     * @param $attribute
     * @param $key
     * @return \Sayla\Objects\ObjectCollection
     */
    public function getWhere($attribute, $value)
    {
        return $this->all()->where($attribute, '=', $value);
    }

    /**
     * @param \Sayla\Objects\ObjectCollection|string $objectCollectionClass
     */
    public function setObjectCollectionClass($objectCollectionClass): void
    {
        $this->objectCollectionClass = $objectCollectionClass;
    }
}