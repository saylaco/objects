<?php

namespace Sayla\Objects\Stores\FileStore;

use Sayla\Objects\Contract\DataObject\SupportsDataTypeManager;
use Sayla\Objects\Contract\DataObject\SupportsDataTypeManagerTrait;
use Sayla\Objects\Contract\Stores\Lookup;
use Sayla\Objects\DataType\DataType;
use Sayla\Objects\DataType\DataTypeManager;
use Sayla\Objects\ObjectCollection;

class ObjectCollectionLookup implements Lookup, SupportsDataTypeManager
{
    use SupportsDataTypeManagerTrait;
    /** @var string */
    protected $dataType;
    /** @var string */
    protected $keyAttribute;
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
        return self::getDataTypeManager()->get($this->dataType)
            ->newCollection()
            ->useKey($this->keyAttribute)
            ->makeObjects($this->records);
    }

    /**
     * @param $key
     * @return \Sayla\Objects\Contract\DataObject\StorableObjectTrait
     */
    public function find($key)
    {
        if (isset($this->records[$key])) {
            return $this->getDataType()->hydrate($this->records[$key]);
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
     * @return \Sayla\Objects\DataType\DataType
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Sayla\Exception\Error
     */
    private function getDataType(): DataType
    {
        return DataTypeManager::resolve()->get($this->dataType);
    }
}