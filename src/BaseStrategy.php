<?php

namespace Sayla\Objects;

use Sayla\Objects\Contract\StoreStrategy;

abstract class BaseStrategy implements StoreStrategy
{
    public function toStoreString()
    {
        return $this->__toString();
    }

    public abstract function __toString(): string;

}