<?php

namespace Sayla\Objects\Contract\Exception;

use Sayla\Exception\Error;

class DataTypeException extends Error
{
    const CODE_NOT_FOUND = 'not-found';

    public static function notFound($name)
    {
        $exception = new self($name);
        $exception->code = self::CODE_NOT_FOUND;
        return $exception;
    }

    public function getSummary()
    {
        return ('(' . $this->getCode() . ') ' . $this->getMessage());
    }
}