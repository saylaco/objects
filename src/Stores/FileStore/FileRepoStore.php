<?php

namespace Sayla\Objects\Stores\FileStore;

use Illuminate\Filesystem\Filesystem;
use Saybol\Support\FileRepo\JsonFileRepository;
use Saybol\Support\FileRepo\PhpFileRepository;
use Saybol\Support\FileRepo\YamlFileRepository;
use Sayla\Objects\Contract\ConfigurableStore;
use Sayla\Objects\Contract\ObjectStore;
use Sayla\Objects\StorableTrait;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UnexpectedValueException;

class FileRepoStore implements ObjectStore, ConfigurableStore
{
    public const FILE_TYPES = ['php' => 'php', 'json' => 'json', 'yml' => 'yml'];
    private $currentId = 0;
    /**
     * @var string
     */
    private $filePath;
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $files;
    /**
     * @var string
     */
    private $name;
    /** @var string */
    private $primaryKey;
    /** @var bool */
    private $primaryKeyIncrements;
    /**
     * @var string
     */
    private $shortName;
    /** @var \Saybol\Support\FileRepo\PhpFileRepository */
    private $store;

    /**
     * SerialStore constructor.
     * @param $data
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    public static function defineOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'primaryKey' => 'id',
            'primaryIncrements' => true,
            'fileType' => 'php',
            'fileSuffix' => '.data',
            'fileDirectory' => null,
            'fileName' => null,
        ]);

        $resolver->setDefault('fileDirectory', function (Options $options) {
            return storage_path('app/data');
        });
        $resolver->setAllowedValues('fileType', self::FILE_TYPES);
    }

    public function create(Storable  $object): iterable
    {
        $dataType = $object->dataType();
        $data = $object->toArray();
        if ($this->primaryKeyIncrements) {
            $key = ++$this->currentId;
        } else {
            $key = $object[$this->primaryKey];
        }
        if (empty($key)) {
            throw new UnexpectedValueException('Key can not be null');
        }
        $data[$this->primaryKey] = $key;
        $this->store->getData()->get('models')->set($key, $data);
        $this->store->save();
        return $dataType->hydrateData($data);
    }

    public function delete(Storable  $object): iterable
    {
        $this->store->getData()->get('models')->forget($object->getKey());
        $this->store->save();
        return $object->toArray();
    }

    public function findModel($key)
    {
        return $this->store->getEntry($key, 'models');
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * @return bool
     */
    public function isPrimaryKeyIncrements(): bool
    {
        return $this->primaryKeyIncrements;
    }

    /**
     * @return \Sayla\Objects\Stores\FileStore\ObjectCollectionLookup
     */
    public function lookup()
    {
        return new ObjectCollectionLookup(
            $this->name,
            $this->getPrimaryKey(),
            $this->store->getGroupArray('models')
        );
    }

    public function setOptions(string $name, array $options): void
    {
        $this->name = $name;
        $this->shortName = studly_case(class_basename($name) ?? $name);
        $this->primaryKey = $options['primaryKey'];
        $this->primaryIncrements = $options['primaryIncrements'];
        if ($this->primaryKeyIncrements) {
            $models = $this->store->getFrom('models', null);
            if (count($models) > 0) {
                $this->currentId = max(array_keys($models->toArray()));
            }
        }
        $fileName = str_finish(
            $options['fileName'] ?? $this->shortName,
            "{$options['fileSuffix']}.{$options['fileType']}"
        );
        $this->filePath = "{$options['fileDirectory']}/{$fileName}";
        if ($options['fileType'] === self::FILE_TYPES['php']) {
            $this->store = new PhpFileRepository($this->files, $this->filePath);
        } elseif ($options['fileType'] === self::FILE_TYPES['json']) {
            $this->store = new JsonFileRepository($this->files, $this->filePath);
        } else {
            $this->store = new YamlFileRepository($this->files, $this->filePath);
        }
        $this->store->loadEntries();
    }

    public function toStoreString(): string
    {
        return 'FileRepo[' . $this->name . ']';
    }

    public function update(Storable  $object): iterable
    {
        $dataType = $object->dataType();
        $data = $object->toArray();
        $this->store->getData()->get('models')->set($object->getKey(), $data);
        $this->store->save();
        return $dataType->hydrateData($data);
    }
}