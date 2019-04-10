<?php

namespace Sayla\Objects\Support\Illuminate;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Sayla\Objects\Contract\ConfigurableStore;
use Sayla\Objects\Contract\ObjectStore;
use Sayla\Objects\Contract\StorableObject;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;


class EloquentStore implements ObjectStore, ConfigurableStore
{
    /** @var Model */
    protected $model;
    protected $useTransactions = false;

    public static function defineOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('model');
        $resolver->setAllowedTypes('model', ['string', Model::class]);
        $resolver->setDefault('useTransactions', false);
        $resolver->setAllowedTypes('useTransactions', 'boolean');
        $resolver->setNormalizer('model', function (Options $options, $model): Model {
            if (is_string($model)) {
                $model = qualify_var_type($model, class_parent_namespace($options['objectClass']));
                return Container::getInstance()->make($model);
            }
            return $model;
        });
    }

    public function __toString(): string
    {
        return $this->toStoreString();
    }

    public function create(StorableObject $object): iterable
    {
        if ($this->useTransactions) {
            return $this->getConnection()->transaction(function () use ($object) {
                return $this->createModel($object);
            });
        }
        return $this->createModel($object);
    }

    protected function createModel(StorableObject $object)
    {
        $data = $object->datatype()->extract($object);
        $model = $this->model->newInstance($data);
        $model->save();
        return $model->getAttributes();
    }

    public function delete(StorableObject $object): iterable
    {
        $model = $this->findModel($object->getKey());
        if ($this->useTransactions) {
            return $this->getConnection()->transaction(function () use ($model, $object) {
                return $this->deleteModel($model, $object);
            });
        }
        return $this->deleteModel($model, $object);
    }

    /**
     * @param Model $model
     * @param \Sayla\Objects\Contract\StorableObject $object
     * @return Model
     */
    protected function deleteModel($model, $object)
    {
        $model->delete();
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
     * @return \Illuminate\Database\Connection
     */
    protected function getConnection()
    {
        return $this->model->getConnection();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    public function setOptions(string $name, array $options): void
    {
        $this->model = $options['model'] ?? $name;
        $this->useTransactions = $options['useTransactions'];
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

    public function toStoreString(): string
    {
        return 'Eloquent[' . get_class($this->model) . ']';
    }

    public function update(StorableObject $object): iterable
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
     * @param Model $model
     * @param \Sayla\Objects\Contract\StorableObject $object
     * @return Model
     */
    protected function updateModel($model, $object)
    {
        $data = $object->datatype()->extract($object);
        $model->fill($data);
        $model->save();
        return $model->getAttributes();
    }

    /**
     * @return bool
     */
    public function usesTransactions(): bool
    {
        return $this->useTransactions;
    }
}