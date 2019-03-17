<?php

namespace Sayla\Objects\Support\Illuminate;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Sayla\Objects\Contract\ConfigurableStore;
use Sayla\Objects\Contract\ObjectStore;
use Sayla\Objects\DataModel;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;


class EloquentStore implements ObjectStore, ConfigurableStore
{
    protected $useTransactions = false;
    /** @var Model */
    protected $model;

    public static function defineOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('model');
        $resolver->setAllowedTypes('model', ['string', \Illuminate\Database\Eloquent\Model::class]);
        $resolver->setDefault('useTransactions', false);
        $resolver->setAllowedTypes('useTransactions', 'boolean');
        $resolver->setNormalizer('model', function (Options $options, $model): \Illuminate\Database\Eloquent\Model {
            if (is_string($model)) {
                return Container::getInstance()->make($model);
            }
            return $model;
        });
    }

    public function setOptions(string $name, array $options): void
    {
        $this->model = $options['model'] ?? $name;
        $this->useTransactions = $options['useTransactions'];
    }

    public function __toString(): string
    {
        return $this->toStoreString();
    }

    public function create(DataModel $object): iterable
    {
        if ($this->useTransactions) {
            return $this->getConnection()->transaction(function () use ($object) {
                return $this->createModel($object);
            });
        }
        return $this->createModel($object);
    }

    public function delete(DataModel $object): iterable
    {
        $model = $this->findModel($object->getKey());
        if ($this->useTransactions) {
            return $this->getConnection()->transaction(function () use ($model, $object) {
                return $this->deleteModel($model, $object);
            });
        }
        return $this->deleteModel($model, $object);
    }

    public function toStoreString(): string
    {
        return 'Eloquent[' . get_class($this->model) . ']';
    }

    public function update(DataModel $object): iterable
    {
        $model = $this->findModel($object->getKey());
        if ($this->useTransactions) {
            return $this->getConnection()->transaction(function () use ($model, $object) {
                return $this->updateModel($model, $object);
            });
        }
        return $this->updateModel($model, $object);
    }

    /**
     * @return \Illuminate\Database\Connection
     */
    protected function getConnection()
    {
        return $this->model->getConnection();
    }

    /**
     * @param \Sayla\Objects\DataModel $object
     * @return array|mixed
     * @throws \Sayla\Exception\Error
     */
    protected function createModel(DataModel $object)
    {
        $data = $object->datatype()->extractData($object);
        $model = $this->model->newInstance($data);
        $model->save();
        return $model->getAttributes();
    }

    /**
     * @param $key
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function findModel($key)
    {
        return $this->model->newQuery()->findOrFail($key);
    }

    /**
     * @param Model $model
     * @param DataModel $object
     * @return Model
     */
    protected function deleteModel($model, $object)
    {
        $model->delete();
        return $model->getAttributes();
    }

    /**
     * @param Model $model
     * @param DataModel $object
     * @return Model
     */
    protected function updateModel($model, $object)
    {
        $data = $object->datatype()->extractData($object);
        $model->fill($data);
        $model->save();
        return $model->getAttributes();
    }

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

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel(): \Illuminate\Database\Eloquent\Model
    {
        return $this->model;
    }
}