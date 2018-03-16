<?php

namespace Sayla\Objects\Exception;

use Illuminate\Contracts\Validation\Validator;

class NamedValidationException extends \Illuminate\Validation\ValidationException
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