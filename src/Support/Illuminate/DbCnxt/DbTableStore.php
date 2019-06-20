<?php

namespace Sayla\Objects\Support\Illuminate\DbCnxt;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Sayla\Objects\Contract\DataObject\Lookable;
use Sayla\Objects\Contract\DataObject\StorableObject;
use Sayla\Objects\Contract\DataObject\SupportsDataTypeManager;
use Sayla\Objects\Contract\DataObject\SupportsDataTypeManagerTrait;
use Sayla\Objects\Contract\Stores\ConfigurableStore;
use Sayla\Objects\Contract\Stores\ModifiesObjectBehavior;
use Sayla\Objects\Contract\Stores\ObjectStore;
use Symfony\Component\OptionsResolver\OptionsResolver;


class DbTableStore implements ObjectStore, ConfigurableStore, ModifiesObjectBehavior, SupportsDataTypeManager
{
    use SupportsDataTypeManagerTrait;
    const STORE_NAME = 'DbTable';
    /**
     * @var \Illuminate\Database\Connection
     */
    protected $connection;
    /** @var \Sayla\Objects\DataType\DataType */
    protected $dataType;
    /** @var string */
    protected $keyName;
    /**
     * @var \Illuminate\Database\Query\Builder
     */
    protected $table;
    /** @var string */
    protected $tableName;
    protected $useTransactions = false;

    public static function defineOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('table');
        $resolver->setAllowedTypes('table', 'string');
        $resolver->setDefault('key', 'id');
        $resolver->setAllowedTypes('key', ['string']);
        $resolver->setDefault('useTransactions', false);
        $resolver->setAllowedTypes('useTransactions', 'boolean');
        $resolver->setDefault('connection', null);
        $resolver->setAllowedTypes('connection', ['string', 'null']);
    }

    public static function getObjectBehavior(): array
    {
        return [
            Lookable::class,
            AsDbRowObject::class
        ];
    }

    public function __toString(): string
    {
        return $this->toStoreString();
    }

    public function create(StorableObject $object): iterable
    {
        if ($this->useTransactions) {
            return $this->newQuery()->getConnection()->transaction(function () use ($object) {
                return $this->createRow($object);
            });
        }
        return $this->createRow($object);
    }

    protected function createRow(StorableObject $object)
    {
        $data = $this->dataType->extract($object);
        $id = $this->newQuery()->insertGetId($data);
        if ($id > 0) {
            return [$this->keyName => $id];
        }
        return [];
    }

    public function delete(StorableObject $object): iterable
    {
        if ($this->useTransactions) {
            return $this->newQuery()->getConnection()->transaction(function () use ($object) {
                return $this->deleteRow($object);
            });
        }
        return $this->deleteRow($object);
    }

    protected function deleteRow($object)
    {
        $this->newQuery()->delete($object->getKey());
        return [];
    }

    public function exists(string $key): bool
    {
        return $this->lookup()->exists($key);
    }

    /**
     * @return string
     */
    public function getKeyName(): string
    {
        return $this->keyName;
    }

    public function lookup()
    {
        return new TableLookup($this->dataType, $this->table, $this->tableName, $this->keyName);
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function newQuery(): Builder
    {
        return $this->table->newQuery()->from($this->tableName);
    }

    public function setOptions(string $name, array $options): void
    {
        $this->model = $options['model'] ?? $name;
        $this->keyName = $options['key'];
        $this->useTransactions = $options['useTransactions'];
        $this->tableName = $options['table'];
        $this->table = DB::connection($options['connection'])->table($options['table']);
        $this->dataType = self::getDataTypeManager()->get($name);
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
        return 'Table[' . $this->tableName . ']';
    }

    public function update(StorableObject $object): iterable
    {
        if ($this->useTransactions) {
            return $this->newQuery()->getConnection()->transaction(function () use ($object) {
                return $this->updateRow($object);
            });
        }
        return $this->updateRow($object);
    }

    protected function updateRow($object)
    {
        $data = $this->dataType->extract($object);
        $this->newQuery()
            ->where($this->keyName, $object->getKey())
            ->update($data);
        return [];
    }

    /**
     * @return bool
     */
    public function usesTransactions(): bool
    {
        return $this->useTransactions;
    }
}