<?php

namespace Sayla\Objects\Stores\FileStore;

use BadMethodCallException;
use Sayla\Objects\Contract\DataObject\StorableObject;

class ReadFileDataStore extends FileDataStore
{
    protected const PRIMARY_INCREMENTS_DEFAULT = false;

    public function create(StorableObject $object): iterable
    {
        throw new BadMethodCallException();
    }

    public function delete(StorableObject $object): iterable
    {
        throw new BadMethodCallException();
    }

    public function toStoreString(): string
    {
        return 'Read' . parent::toStoreString();
    }

    public function update(StorableObject $object): iterable
    {
        throw new BadMethodCallException();
    }
}