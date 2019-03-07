<?php

namespace Sayla\Objects\Stores;

use Saybol\Support\Stores\PhpDataStore;
use Sayla\Objects\Contract\ConfigurableStore;
use Sayla\Objects\Contract\ObjectStore;
use Sayla\Objects\DataModel;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArrayStore implements ObjectStore, ConfigurableStore
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $files;
    /**
     * @var string
     */
    private $name;
    private $currentId = 0;
    /** @var \Saybol\Support\Stores\PhpDataStore */
    private $store;
    /** @var string */
    private $primaryKey;
    /** @var bool */
    private $primaryKeyIncrements;

    /**
     * SerialStore constructor.
     * @param $data
     */
    public function __construct(\Illuminate\Filesystem\Filesystem $files)
    {
        $this->files = $files;
    }

    public static function defineOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'primaryKey' => 'id',
            'primaryIncrements' => true
        ]);
    }

    public function setOptions(string $name, array $options): void
    {
        $this->name = studly_case(class_basename($name) ?? $name);
        $this->primaryKey = $options['primaryKey'];
        $this->primaryIncrements = $options['primaryIncrements'];
        if ($this->primaryKeyIncrements) {
            $models = $this->store->getFrom('models', null);
            if (count($models) > 0) {
                $this->currentId = max(array_keys($models->toArray()));
            }
        }
        $this->store = new PhpDataStore($this->files, storage_path('app/data/' . $this->name . '.data.php'));
        $this->store->loadEntries();
    }

    public function create(DataModel $object): iterable
    {
        $dataType = $object->dataType();
        $data = $object->toArray();
        if ($this->primaryKeyIncrements) {
            $key = ++$this->currentId;
        } else {
            $key = $object[$this->primaryKey];
        }
        if (empty($key)) {
            throw new \UnexpectedValueException('Key can not be null');
        }
        $data[$this->primaryKey] = $key;
        $this->store->getData()->get('models')->set($key, $data);
        $this->store->save();
        return $dataType->hydrateData($data);
    }

    public function delete(DataModel $object): iterable
    {
        $this->store->getData()->get('models')->forget($object->getKey());
        $this->store->save();
        return $object->toArray();
    }

    public function update(DataModel $object): iterable
    {
        $dataType = $object->dataType();
        $data = $object->toArray();
        $this->store->getData()->get('models')->set($object->getKey(), $data);
        $this->store->save();
        return $dataType->hydrateData($data);
    }

    public function findModel($key)
    {
        return $this->store->getEntry($key, 'models');
    }

    public function toStoreString(): string
    {
        return 'ArrayStore[' . get_class($this->name) . ']';
    }
}