<?php

namespace Sayla\Objects\Exception;

use Sayla\Exception\Error;

class InaccessibleAttribute extends Error
{

    public function __construct(string $class, string $attribute, string $message = null)
    {
        parent::__construct($class . '.' . $attribute . ($message ? ' - ' . $message : ''));
    }
}