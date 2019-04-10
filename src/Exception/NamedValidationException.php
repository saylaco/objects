<?php

namespace Sayla\Objects\Exception;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class NamedValidationException extends ValidationException
{
    public $entityName;

    public function __construct(Validator $validator, string $entityName, $response = null)
    {
        $this->entityName = $entityName;
        parent::__construct($validator, $response);
        $message = $entityName . ' is invalid';
        $this->message = $message . ': ' . join(' ', $validator->getMessageBag()->all());
    }
}