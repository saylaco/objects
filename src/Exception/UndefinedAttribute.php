<?php

namespace Sayla\Objects\Exception;

use Sayla\Exception\Error;

class UndefinedAttribute extends Error
{

    public function __construct(string $class, string $attribute)
    {
        parent::__construct($class . '.' . $attribute);
    }
}