<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Contract\DataObject\StorableObject;
use Sayla\Objects\Contract\Triggerable;
use Sayla\Objects\Validation\ValidationBuilder;
use Sayla\Util\Mixin\Mixin;

class RulesDescriptorMixin implements Mixin
{
    const ON_VALIDATE = 'beforeValidate';
    const ON_VALIDATE_CREATE = 'beforeValidateCreate';
    const ON_VALIDATE_DELETE = 'beforeValidateDelete';
    const ON_VALIDATE_UPDATE = 'beforeValidateUpdate';
    private static $registered = [];
    private static $rules = [];
    /**
     * @var string
     */
    private $dataType;
    private $properties;

    public function __construct(string $dataType, array $properties)
    {
        $this->properties = collect($properties)->filter();
        $this->dataType = $dataType;
    }

    public function getCreateValidationBuilder($object = null)
    {
        return $this->getValidationBuilder($object)
            ->appendRules($this->properties->map(function ($prop) {
                return $prop['rules']['save'];
            })->toArray())
            ->appendRules($this->getInstanceRules('save', $object))
            ->appendRules($this->properties->map(function ($prop) {
                return $prop['rules']['create'];
            })->toArray())
            ->appendRules($this->getInstanceRules('create', $object));
    }

    /**
     * @return \Sayla\Objects\Validation\ValidationBuilder
     */
    protected function getDataTypeValidationBuilder(): ValidationBuilder
    {
        if (!isset(self::$registered[$this->dataType]['builder'])) {
            $builder = (new ValidationBuilder($this->dataType));
            $factory = $builder->getFactory();

            foreach ($this->properties->flatMap->validators as $validatorInfo) {
                $callback = $validatorInfo['callback'];
                $handlers = $this->getHandlers($callback)[$validatorInfo['key']];
                if ($validatorInfo['replacer']) {
                    $factory->extend($validatorInfo['name'], $handlers[0]);
                    $factory->replacer($validatorInfo['name'], $handlers[1]);

                } else {
                    $factory->extend($validatorInfo['name'], $handlers);
                }
            }

            $builder->setRules($this->properties->map(function ($prop) {
                return $prop['rules'][Rules::SHARED_RULES];
            })->toArray())
                ->setMessages($this->properties->map(function ($prop) {
                    return $prop['errMsg'];
                })->filter()->toArray())
                ->setCustomAttributes($this->properties->map(function ($prop) {
                    return $prop['label'];
                })->filter()->toArray());

            $builder->setValidationFactory($factory);

            self::$registered[$this->dataType]['builder'] = $builder;
        }

        $builder = clone self::$registered[$this->dataType]['builder'];
        return $builder;
    }

    public function getDeleteValidationBuilder($object = null)
    {
        $builder = $this->getValidationBuilder($object);
        $builder
            ->appendRules($this->properties->map(function ($prop) {
                return $prop['rules']['delete'];
            })->toArray())
            ->appendRules($this->getInstanceRules('delete', $object));
        return $builder;
    }

    protected function getInstanceRules(string $ruleSet, $object = null)
    {
        return $this->mergeEventResults($object, $ruleSet . 'ValidationRules', true);
    }

    public function getUpdateValidationBuilder($object = null)
    {
        return $this->getValidationBuilder($object)
            ->appendRules($this->properties->map(function ($prop) {
                return $prop['rules']['save'];
            })->toArray())
            ->appendRules($this->getInstanceRules('save', $object))
            ->appendRules($this->properties->map(function ($prop) {
                return $prop['rules']['update'];
            })->toArray())
            ->appendRules($this->getInstanceRules('update', $object));
    }

    /**
     * @return \Sayla\Objects\Validation\ValidationBuilder
     */
    protected function getValidationBuilder(Triggerable $object = null): ValidationBuilder
    {
        return $this->getDataTypeValidationBuilder()
            ->setCustomAttributes($this->mergeEventResults($object, 'validationCustomAttributes'))
            ->setMessages($this->mergeEventResults($object, 'validationMessages'));
    }

    /**
     * @param \Sayla\Objects\Contract\Triggerable|null $object
     * @param string $event
     * @param bool $recursive
     * @return array
     */
    protected function mergeEventResults(?Triggerable $object, string $event, $recursive = false): array
    {
        if ($object && $object->hasTriggerListeners($event)) {
            $results = $object($event);
            return $recursive
                ? array_merge_recursive(...$results['instance'])
                : array_merge(...$results['instance']);
        }
        return [];
    }

    /**
     * @param \Sayla\Objects\Contract\DataObject\StorableObject $object
     */
    public function validateCreate(StorableObject $object)
    {
        $object(self::ON_VALIDATE);
        $object(self::ON_VALIDATE_CREATE);
        $this->getCreateValidationBuilder($object)->validate($object->toArray());
    }

    /**
     * @param \Sayla\Objects\Contract\DataObject\StorableObject $object
     */
    public function validateDelete(StorableObject $object)
    {
        $object(self::ON_VALIDATE);
        $object(self::ON_VALIDATE_DELETE);
        $this->getDeleteValidationBuilder($object)->validate($object->toArray());
    }

    /**
     * @param \Sayla\Objects\Contract\DataObject\StorableObject $object
     */
    public function validateUpdate(StorableObject $object)
    {
        $object(self::ON_VALIDATE);
        $object(self::ON_VALIDATE_UPDATE);
        $validationBuilder = $this->getUpdateValidationBuilder($object);
        $validationBuilder->validate($object->toArray());
    }

    /**
     * @param $callback
     * @return array
     */
    private function getHandlers($callback): array
    {
        if (!isset(self::$rules[$callback])) {
            self::$rules[$callback] = call_user_func($callback);
        }
        return self::$rules[$callback];
    }
}