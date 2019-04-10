<?php

namespace Sayla\Objects\Validation;

class ValidationDescriptor
{
    public $createRules;
    public $deleteRules;
    public $label;
    public $labels;
    public $messages;
    public $rules;
    public $updateRules;
    private $name;

    /**
     * ValidationDescriptor constructor.
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

}