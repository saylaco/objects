<?php

namespace Sayla\Objects\DataType;

use Sayla\Objects\Builder\Builder;
use Sayla\Objects\Contract\RegistrarRepository;
use Sayla\Objects\Stores\StoreManager;

class DataTypeRegistrar
{
    /**
     * @var \Sayla\Objects\DataType\DataTypeManager
     */
    private $dataTypes;
    /**
     * @var \Sayla\Objects\Contract\RegistrarRepository
     */
    private $repository;
    /**
     * @var \Sayla\Objects\Stores\StoreManager
     */
    private $stores;

    public function __construct(DataTypeManager $dataTypes, StoreManager $stores, RegistrarRepository $repository)
    {
        $this->repository = $repository;
        $this->dataTypes = $dataTypes;
        $this->stores = $stores;
    }

    /**
     * @param string $objectName
     * @param Builder|\Closure $builder
     */
    public function addDataType(string $objectName, $builder)
    {
        if ($builder instanceof \Closure) {
            $resolver = $builder;
            $builder = new Builder($objectName);
            call_user_func($resolver, $builder);
        }
        $this->repository->addObject($objectName, $builder);
    }

    public function addDataTypes()
    {
        foreach ($this->repository->getObjects() as $info) {
            if ($info->store) {
                $this->stores->addStore($info->store['name'], $info->store['driver'], $info->store['options']);
            }
            $this->dataTypes->add(Builder::makeDataType(
                $info->dataTypeClass,
                $this->repository->getOptions($info->name)
            ));
        }
    }
}