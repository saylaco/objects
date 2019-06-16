<?php

namespace Sayla\Objects\Exception;

use Sayla\Exception\Error;

class TransformationError extends Error
{
    /**
     * TransformationError constructor.
     */
    public function __construct($message, $previous = null, $code = null)
    {
        parent::__construct(self::appendPreviousMessage($message, $previous), $previous, $code);
    }
}