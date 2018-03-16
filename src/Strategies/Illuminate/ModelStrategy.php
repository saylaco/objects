<?php

namespace Sayla\Objects\Strategies\Illuminate;

use Sayla\Objects\BaseDataModel;
use Sayla\Objects\Contract\StoreStrategy;


abstract class ModelStrategy implements StoreStrategy
{
    protected $useTransactions = false;

    public function __toString(): string
    {
        return $this->toStoreString();
    }

    public function create(BaseDataModel $object): iterable
    {
        if ($this->useTransactions) {
            return $this->getConnection()->transaction(function () use ($object) {
                return $this->createModel($object);
            });
        }
        return $this->createModel($object);
    }

    public function delete(BaseDataModel $object): iterable
    {
        $model = $this->findModel($object->getKey());
        if ($this->useTransactions) {
            $this->getConnection()->transaction(function () use ($model, $object) {
                $this->deleteModel($model, $object);
            });
        } else {
            $this->deleteModel($model, $object);
        }
        return $model->getAttributes();
    }

    public function update(BaseDataModel $object): ?iterable
    {
        $model = $this->findModel($object->getKey());
        if ($this->useTransactions) {
            $this->getConnection()->transaction(function () use ($model, $object) {
                $this->updateModel($model, $object);
            });
        } else {
            $this->updateModel($model, $object);
        }
        return $model->getAttributes();
    }

    /**
     * @return \Illuminate\Database\Connection
     */
    protected abstract function getConnection(): \Illuminate\Database\Connection;

    /**
     * @param $key
     * @return mixed
     */
    protected function findModel($key)
    {
        return $this->newQuery()->find($key);
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    protected abstract function newQuery();

    /**
     * @param bool $useTransactions
     * @return $this
     */
    public function setUseTransactions(bool $useTransactions)
    {
        $this->useTransactions = $useTransactions;
        return $this;
    }

    /**
     * @return bool
     */
    public function usesTransactions(): bool
    {
        return $this->useTransactions;
    }
}