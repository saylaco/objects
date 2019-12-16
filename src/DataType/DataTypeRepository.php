<?php

namespace Sayla\Objects\DataType;

use Saybol\Support\FileRepo\FileRepo;
use Sayla\Objects\Builder\DataTypeConfig;
use Sayla\Objects\Contract\DataTypeConfigCache;

class DataTypeRepository implements DataTypeConfigCache
{
    /** @var \Saybol\Support\FileRepo\FileRepo */
    protected $repo;

    public function __construct(FileRepo $repository)
    {
        $this->repo = $repository;
    }

    public function addConfig(DataTypeConfig $builder)
    {
        $entry = $this->repo->group('dataTypes')->get($builder->getName());
        $entry->set('options', serialize($builder->enableOptionsValidation()->getOptions()));
        $entry->save();
    }


    public function flush()
    {
        $this->repo->group('dataTypes')->purge();
    }

    public function getAllDataTypeConfigs(): iterable
    {
        return collect($this->repo->getGroupArray('dataTypes'))
            ->map(function ($info) {
                return unserialize($info['options']);
            });
    }

    /**
     * @return \Saybol\Support\FileRepo\FileRepo
     */
    public function getRepo(): FileRepo
    {
        return $this->repo;
    }
}
