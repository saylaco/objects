<?php

namespace Sayla\Objects\Contract;

interface PersistentDataType extends DataType
{

    public function getStoreStrategy(): ObjectStore;

    public function onCreate($listener);

    public function onDelete($listener);

    public function onUpdate($listener);
}