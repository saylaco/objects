<?php

namespace Sayla\Objects\Contract;

interface PersistentDataType extends DataType
{

    public function getStoreStrategy(): ObjectStore;
}