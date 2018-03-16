<?php

namespace Sayla\Objects\Strategies;

use Illuminate\Support\Collection;
use Sayla\Exception\RecordNotFound;
use Sayla\Objects\BaseDataModel;
use Sayla\Objects\BaseStrategy;
use Sayla\Objects\Transformers\Transformer;

class CollectionStrategy extends BaseStrategy
{
    private static $collections = [];
    /** @var \Sayla\Objects\Transformers\Transformer */
    private $transformer;
    private $idCounter = 1;
    /** @var \Illuminate\Support\Collection */
    private $records;
    /** @var string */
    private $primaryKeyName;
    private $collectionName;

    /**
     * BlockRepository constructor.
     * @param string $name
     * @param \Sayla\Objects\Transformers\Transformer $transformer
     * @param string $primaryKey
     */
    public function __construct(string $name, Transformer $transformer, string $primaryKey)
    {
        $this->collectionName = $name;
        $this->transformer = $transformer;
        $this->primaryKeyName = $primaryKey;
        if (!isset(static::$collections[$name])) {
            static::$collections[$name] = collect();
        }
        $this->records = self::getRecords($name);
    }

    /**
     * @param string $collectionName
     * @return mixed
     */
    public static function getRecords(string $collectionName): Collection
    {
        return static::$collections[$collectionName];
    }

    public function __toString(): string
    {
        return 'Collection[' . $this->collectionName . '#' . $this->primaryKeyName . ']';
    }

    public function create(BaseDataModel $object): iterable
    {
        $attributes = $object->descriptor()->remapAttributesForStore($object);
        $record = $this->smashAttributes($attributes);
        $id = $this->getNextId();
        $record[$this->primaryKeyName] = $id;
        $this->store($id, $record);
        return $record;
    }

    public function delete(BaseDataModel $object): ?iterable
    {
        unset($this->records[$object->getKey()]);
        return null;
    }

    public function update(BaseDataModel $object): ?iterable
    {
        $identifier = $object->getKey();
        $attributes = $object->descriptor()->remapAttributesForStore($object);
        $smashedAttributes = $this->smashAttributes($attributes);
        $record = $this->retrieve($identifier);
        if (!isset($record)) {
            throw new  RecordNotFound($identifier);
        }
        $record = array_merge($record, $smashedAttributes);
        $this->store($identifier, $record);
    }

    /**
     * @param iterable $attributes
     * @return array
     */
    public function smashAttributes(iterable $attributes): array
    {
        $attributes = array_only($attributes, $this->transformer->getAttributeNames());
        $smashedAttributes = $this->transformer->smashAll($attributes);
        return $smashedAttributes;
    }

    /**
     * @return int
     */
    protected function getNextId(): int
    {
        $id = $this->idCounter;
        $this->idCounter++;
        return $id;
    }

    /**
     * @param $identifier
     * @param $record
     */
    protected function store($identifier, $record): void
    {
        $this->records->put($identifier, $record);
    }

    /**
     * @param $identifier
     * @return mixed
     */
    protected function retrieve($identifier)
    {
        return $this->records->get($identifier);
    }

    protected function getValidationBuilderProperties(): array
    {
        return [];
    }
}