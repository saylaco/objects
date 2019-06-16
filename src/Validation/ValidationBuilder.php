<?php

namespace Sayla\Objects\Validation;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Sayla\Data\ArrayObject;
use Sayla\Objects\Exception\EntityValidationException;

class ValidationBuilder
{
    /** @var  \Illuminate\Contracts\Validation\Factory */
    protected static $sharedValidationFactory;
    /** @var string[] */
    protected $customAttributes = [];
    /** @var string */
    protected $entityName;
    /** @var string[] */
    protected $messages = [];
    /** @var \Sayla\Data\ArrayObject */
    protected $properties;
    /** @var string[]|mixed[] */
    protected $rules = [];
    /** @var bool */
    protected $useDataAsProperties = true;
    /** @var  \Illuminate\Contracts\Validation\Factory */
    protected $validationFactory;

    /**
     * RulesParser constructor.
     * @param string $entityName
     * @param array $properties
     */
    public function __construct($entityName, $properties = [])
    {
        $this->entityName = $entityName;
        $this->properties = new ArrayObject($properties);
    }

    /**
     * @param \Illuminate\Contracts\Validation\Factory $validator
     */
    public static function setSharedValidationFactory(Factory $validator)
    {
        self::$sharedValidationFactory = $validator;
    }

    /**
     * @param array $rules
     * @return $this
     */
    public function appendRules(array $rules)
    {
        $this->rules = $this->mergeRules($rules);
        return $this;
    }

    /**
     * @param array $data
     * @param array|iterable $rules
     * @param array|null $messages
     * @param array|null $customAttributes
     * @return \Illuminate\Validation\Validator
     */
    public function build(array $data = [],
                          array $rules = null,
                          array $messages = null,
                          array $customAttributes = null): Validator
    {
        $rules = $this->mergeRules($rules);
        if ($this->useDataAsProperties) {
            $preparedRules = $this->prepareRules($rules, $data);
        } else {
            $preparedRules = $this->prepareRules($rules);
        }
        $messages = $this->mergeMessages($messages);
        $customAttributes = $this->mergeCustomAttributes($customAttributes);
        return $this->getFactory()->make($data, $preparedRules, $messages, $customAttributes);
    }

    /**
     * @return \Illuminate\Contracts\Validation\Factory
     */
    public function getFactory(): Factory
    {
        return $this->validationFactory ?? self::$sharedValidationFactory;
    }

    /**
     * @param array $customAttributes
     * @return array
     */
    public function mergeCustomAttributes(array $customAttributes = null): array
    {
        if (empty($customAttributes)) {
            $customAttributes = $this->customAttributes;
        } else {
            $customAttributes = array_replace_recursive($this->customAttributes, $customAttributes);
        }
        return $customAttributes;
    }

    /**
     * @param array $messages
     * @return array
     */
    public function mergeMessages(array $messages = null): array
    {
        if (empty($messages)) {
            $messages = $this->messages;
        } else {
            $messages = array_replace_recursive($this->messages, $messages);
        }
        return $messages;
    }

    /**
     * @param array $rules
     * @return array
     */
    public function mergeRules(array $rules = null): array
    {
        if (empty($rules)) {
            $rules = $this->rules;
        } else {
            $rules = array_replace_recursive($this->rules, $rules);
        }
        return $rules;
    }

    /**
     * @param iterable $allRules
     * @param array|null $extraProperties
     * @return iterable
     */
    public function prepareRules(iterable $allRules, array $extraProperties = null)
    {
        $properties = $this->properties;
        if ($extraProperties) {
            $properties = new ArrayObject(array_merge($properties->toArray(), $extraProperties));
        }
        if (count($properties) == 0) {
            return $allRules;
        }
        foreach ($allRules as $attr => $rules) {
            if (!is_array($rules)) {
                $rules = explode('|', $rules);
            }
            $toAppend = [];
            foreach ($rules as $i => $rule) {
                if (!is_string($rule)) {
                    $toAppend[] = $rule;
                } elseif (preg_match_all('/(null|Null|NULL)$|\$([\.\w]+)/', $rule, $matches, PREG_SET_ORDER)) {
                    $replace = [];
                    $search = [];
                    foreach ($matches as $match) {
                        $search[] = $match[0];
                        switch ($match[2]) {
                            case 'null':
                            case 'Null':
                            case 'NULL':
                                $value = null;
                                break;
                            case $match[2] == '__value':
                                $value = array_get($properties, $attr);
                                break;
                            default:
                                $value = array_get($properties, $match[2]);
                        }
                        $replace[] = is_null($value) ? 'NULL' : $value;
                    }
                    $rules[$i] = str_replace($search, $replace, $rules[$i]);
                }
            }
            if (!empty($toAppend)) {
                $rules = array_merge($rules, $toAppend);
            }
            $allRules[$attr] = $rules;
        }
        return $allRules;
    }

    /**
     * @param \string[] $customAttributes
     * @return ValidationBuilder
     */
    public function setCustomAttributes(array $customAttributes): ValidationBuilder
    {
        $this->customAttributes = $customAttributes;
        return $this;
    }

    /**
     * @param \string[] $messages
     * @return ValidationBuilder
     */
    public function setMessages(array $messages): ValidationBuilder
    {
        $this->messages = $messages;
        return $this;
    }

    /**
     * @param \mixed[]|\string[] $rules
     * @return ValidationBuilder
     */
    public function setRules($rules)
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * @param bool $useDataAsProperties
     * @return $this
     */
    public function setUseDataAsProperties(bool $useDataAsProperties)
    {
        $this->useDataAsProperties = $useDataAsProperties;
        return $this;
    }

    /**
     * @param \Illuminate\Contracts\Validation\Factory $validationFactory
     * @return $this
     */
    public function setValidationFactory(Factory $validationFactory)
    {
        $this->validationFactory = $validationFactory;
        return $this;
    }

    /**
     * @param array $data
     * @param array|iterable $rules
     * @param array|null $messages
     * @param array|null $customAttributes
     * @return \Illuminate\Contracts\Validation\Validator
     * @throws \Sayla\Objects\Exception\EntityValidationException
     */
    public function validate(array $data,
                             array $rules = null,
                             array $messages = null,
                             array $customAttributes = null): Validator
    {
        $validator = $this->build($data, $rules, $messages, $customAttributes);
        if ($validator->fails()) {
            throw new EntityValidationException($validator, $this->entityName);
        }
        return $validator;
    }
}