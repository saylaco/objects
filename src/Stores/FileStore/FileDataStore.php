<?php

namespace Sayla\Objects\Stores\FileStore;

use Saybol\Support\FileRepo\FileGroup;
use Saybol\Support\FileRepo\FileRecord;
use Saybol\Support\FileRepo\FileRepo;
use Sayla\Objects\Contract\DataObject\Lookable;
use Sayla\Objects\Contract\DataObject\StorableObject;
use Sayla\Objects\Contract\Stores\ConfigurableStore;
use Sayla\Objects\Contract\Stores\FileStore\FiltersRecords;
use Sayla\Objects\Contract\Stores\ModifiesObjectBehavior;
use Sayla\Objects\Contract\Stores\ObjectStore;
use Sayla\Objects\DataType\DataTypeManager;
use Symfony\Component\OptionsResolver\Options;
use UnexpectedValueException;

class FileDataStore implements ObjectStore, ConfigurableStore, ModifiesObjectBehavior
{
    protected const PRIMARY_INCREMENTS_DEFAULT = true;
    private static $currentId = [];
    private static $repos = [];
    /** @var FileGroup */
    private $group;
    /**
     * @var string
     */
    private $name;
    /** @var bool */
    private $primaryIncrements;
    /** @var string */
    private $primaryKey;
    /**
     * @var \Saybol\Support\FileRepo\FileRepo
     */
    private $repo;
    /**
     * @var string
     */
    private $shortName;

    public static function defineOptions($resolver): void
    {
        $resolver->setDefaults([
            'primaryKey' => 'id',
            'primaryIncrements' => static::PRIMARY_INCREMENTS_DEFAULT,
            'fileRepo' => null,
            'fileGroup' => null,
            'fileType' => 'php',
            'fileDirectory' => null,
            'fileName' => null,
        ]);

        $resolver->setDefault('fileDirectory', function (Options $options) {
            return storage_path('app/data');
        });
        $resolver->setAllowedValues('fileType', FileRepo::FILE_TYPES);
        $resolver->setAllowedTypes('fileRepo', ['string', 'null']);
        $resolver->setAllowedTypes('fileGroup', ['string', 'null']);
    }

    public static function getObjectBehavior(): array
    {
        return [
            Lookable::class,
            LooksUpFileRepoTrait::class,
        ];
    }

    public function __destruct()
    {
        if (isset(self::$currentId[$this->shortName])) {
            $this->getMetadata()
                ->set('nextPrimaryKey', self::$currentId[$this->shortName])
                ->save();
        }
    }

    public function create(StorableObject $object): iterable
    {
        $dataType = $object->dataType();
        $data = $dataType->extract($object);
        if ($this->primaryIncrements) {
            $key = $this->getNextPrimaryKeyValue();
        } else {
            $key = $object[$this->primaryKey];
            if (empty($key)) {
                throw new UnexpectedValueException('Key can not be null');
            }
        }
        $data[$this->primaryKey] = $key;
        $entry = $this->group->replace($key);
        $entry->fill($data)->save();
        return $dataType->hydrateData($data);
    }

    public function delete(StorableObject $object): iterable
    {
        $this->group->remove($object->getKey());
        return $object->toArray();
    }

    public function exists(string $key): bool
    {
        return $this->group->has($key);
    }

    public function findModel($key)
    {
        return $this->group->get($key);
    }

    protected function getNextPrimaryKeyValue(): int
    {
        if (!$this->primaryIncrements) {
            throw new UnexpectedValueException('Primary key is non incrementing');
        }
        if (!isset(self::$currentId[$this->shortName])) {
            self::$currentId[$this->shortName] = $this->getPrimaryKeyValueMetadata();
        }
        return (++self::$currentId[$this->shortName]);
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
    public function isPrimaryKeyIncrementing(): bool
    {
        return $this->primaryIncrements;
    }

    /**
     * @return \Sayla\Objects\Stores\FileStore\ObjectCollectionLookup
     */
    public function lookup()
    {
        /** @var FiltersRecords $objectClass */
        $objectClass = DataTypeManager::resolve()->getObjectClass($this->name);
        if (is_subclass_of($objectClass, FiltersRecords::class)) {
            $records = $objectClass::applyFilters($this->group);
        } else {
            $records = $this->group;
        }
        return new ObjectCollectionLookup(
            $this->name,
            $this->getPrimaryKey(),
            $records
        );
    }

    public function setOptions(string $name, array $options): void
    {
        $this->name = $name;
        $this->shortName = str_replace('\\', '_', studly_case($name));
        $this->primaryKey = $options['primaryKey'];
        $this->primaryIncrements = $options['primaryIncrements'];
        $repoKey = $options['fileDirectory'] . $options['fileType'];
        $this->repo = isset($options['fileRepo'])
            ? resolve($options['fileRepo'])
            : (
                self::$repos[$repoKey]
                ?? self::$repos[$repoKey] = FileRepo::make($options['fileDirectory'], $options['fileType'])
            );
        $this->group = isset($options['fileGroup'])
            ? resolve($options['fileGroup'])
            : $this->repo->group($this->shortName);
    }

    public function toStoreString(): string
    {
        return 'FileRepo[' . $this->name . ']';
    }

    public function update(StorableObject $object): iterable
    {
        $dataType = $object->dataType();
        $data = $dataType->extract($object);
        $entry = $this->group->replace($object->getKey());
        $entry->fill($data)->save();
        return $dataType->hydrateData($data);
    }

    /**
     * @return \Saybol\Support\FileRepo\FileRecord
     */
    private function getMetadata(): FileRecord
    {
        $meta = $this->repo->group('_meta')->get($this->shortName);
        return $meta;
    }

    private function getPrimaryKeyValueMetadata(): int
    {
        return max(
            intval($this->getMetadata()->get('nextPrimaryKey')),
            intval($this->group->getLastEntryName())
        );
    }
}