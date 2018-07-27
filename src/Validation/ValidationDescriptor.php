<?php

namespace Sayla\Objects\Validation;

class ValidationDescriptor
{
    public $rules;
    public $label;
    public $messages;
    public $deleteRules;
    public $updateRules;
    public $createRules;
    public $labels;
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