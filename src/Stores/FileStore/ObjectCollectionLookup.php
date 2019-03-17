<?php

namespace Sayla\Objects\Stores\FileStore;

use Sayla\Objects\ObjectCollection;

class ObjectCollectionLookup
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
     * @return \Sayla\Objects\ObjectCollection|\Sayla\Objects\DataModel[]
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
     * @return \Sayla\Objects\DataModel
     */
    public function find($key)
    {
        return $this->all()->firstWhere($this->keyAttribute, '=', $key);
    }

    /**
     * @param $attribute
     * @param $key
     * @return \Sayla\Objects\DataModel
     */
    public function findBy($attribute, $key)
    {
        return $this->all()->firstWhere($attribute, '=', $key);
    }

    /**
     * @param \Sayla\Objects\ObjectCollection|string $objectCollectionClass
     */
    public function setObjectCollectionClass($objectCollectionClass): void
    {
        $this->objectCollectionClass = $objectCollectionClass;
    }
}