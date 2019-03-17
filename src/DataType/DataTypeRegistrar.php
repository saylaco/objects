<?php

namespace Sayla\Objects\DataType;

use Sayla\Objects\Builder\Builder;
use Sayla\Objects\Contract\RegistrarRepository;
use Sayla\Objects\Stores\StoreManager;

class DataTypeRegistrar
{
    /**
     * @var \Sayla\Objects\Contract\RegistrarRepository
     */
    private $repository;

    public function __construct(RegistrarRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param string $objectName
     * @param Builder|\Closure $builder
     * @return Builder
     */
    public function addDataType(string $objectName, $builder): Builder
    {
        if ($builder instanceof \Closure) {
            $resolver = $builder;
            $builder = new Builder($objectName);
            call_user_func($resolver, $builder);
        }
        $this->repository->addObject($objectName, $builder);
        return $builder;
    }

    public function addDataTypes(DataTypeManager $dataTypes, StoreManager $stores)
    {
        foreach ($this->repository->getObjects() as $info) {
            if ($info->store) {
                $stores->addStore($info->store['name'], $info->store['options']);
            }
            $dataTypes->add(Builder::makeDataType(
                $info->dataTypeClass,
                $this->repository->getOptions($info->name)
            ));
        }
    }
}