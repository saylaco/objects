<?php

namespace Sayla\Objects\Exception;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EntityValidationException extends ValidationException
{
    /** @var \Closure|callable */
    private static $responseBuilder;
    public $entityName;

    public function __construct(Validator $validator, string $entityName, $response = null)
    {
        parent::__construct($validator, $response ?? $this->buildResponse($validator, $entityName));
        $this->entityName = $entityName;
        $this->message = join(' ', $validator->getMessageBag()->all());
    }

    /**
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @param string $entityName
     * @return \Illuminate\Contracts\Support\Responsable|\Sayla\Objects\Exception\EntityValidationException
     */
    public static function make(Validator $validator, string $entityName)
    {
        if (!self::$responseBuilder) {
            return new self($validator, $entityName);
        }
        return new class($validator, $entityName) extends EntityValidationException implements Responsable
        {

            /**
             * Create an HTTP response that represents the object.
             *
             * @param \Illuminate\Http\Request $request
             * @return \Illuminate\Http\Response
             */
            public function toResponse($request): Response
            {
                return $this->response ?? $this->buildResponse($this->validator, $this->entityName);
            }
        };
    }

    /**
     * @param callable|\Closure $responseBuilder
     */
    public static function setResponseBuilder($responseBuilder): void
    {
        self::$responseBuilder = $responseBuilder;
    }

    protected function buildResponse(Validator $validator, string $entityName)
    {
        if (self::$responseBuilder) {
            return call_user_func(self::$responseBuilder, $validator, $entityName);
        }
        return null;
    }

    /**
     * Get all of the validation error messages.
     *
     * @return array
     */
    public function errors()
    {
        $errors = [];
        foreach ($this->validator->failed() as $attr => $msgs) {
            $errors[$attr] = array_combine(array_map('lcfirst', array_keys($msgs)), array_fill(0, count($msgs), true));
        }
        $messages = $this->validator->messages();
        return compact('errors', 'messages');
    }
}