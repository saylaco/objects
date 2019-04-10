<?php

namespace Sayla\Objects\Stores;

use Sayla\Objects\Contract\ObjectStore;
use Sayla\Objects\DataObject;
use Sayla\Objects\Validation\ValidationBuilder;
use Sayla\Objects\Validation\ValidationDescriptor;

class ValidatingStorageDelegate implements ObjectStore
{
    /**
     * @var \Sayla\Objects\Validation\ValidationDescriptor
     */
    private $descriptor;
    private $properties = [];
    /**
     * @var ObjectStore
     */
    private $strategy;

    /**
     * ObjectStore constructor.
     * @param \Sayla\Objects\Contract\ObjectStore $strategy
     */
    public function __construct(ValidationDescriptor $descriptor, ObjectStore $strategy)
    {
        $this->strategy = $strategy;
        $this->descriptor = $descriptor;
    }

    /**
     * @param \Sayla\Objects\DataObject $object
     */
    public function create(DataObject $object)
    {
        $object('beforeCreate');
        $this->performCreate($object);
        $object->clearModifiedAttributeFlags();
        $object('afterCreate');
    }

    /**
     * @param \Sayla\Objects\DataObject $object
     */
    public function delete(DataObject $object)
    {
        $object('beforeValidation');
        $object('beforeDeleteValidation');
        $this->validateDeletion($object);
        $this->strategy->delete($object);
    }

    /**
     * @return array
     */
    protected function getCreateRules(): array
    {
        return array_merge_recursive($this->descriptor->rules, $this->descriptor->createRules);
    }

    /**
     * @return array
     */
    protected function getDeleteRules(): array
    {
        return array_merge_recursive($this->descriptor->rules, $this->descriptor->deleteRules);
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties)
    {
        $this->properties = array_merge($this->properties, $properties);
        return $this;
    }

    public function getStrategy(): ObjectStore
    {
        return $this->strategy;
    }

    /**
     * @return array
     */
    protected function getUpdateRules(): array
    {
        return array_merge_recursive($this->descriptor->rules, $this->descriptor->updateRules);
    }

    /**
     * @return ValidationBuilder
     */
    public function getValidationBuilder()
    {
        $builder = (new ValidationBuilder($this->descriptor->getName(), $this->properties))
            ->setMessages($this->descriptor->messages)
            ->setCustomAttributes($this->descriptor->labels);
        return $builder;
    }

    protected function getValidationDescriptor(): ValidationDescriptor
    {
        return $this->descriptor;
    }

    protected function performCreate(DataObject $object)
    {
        $object('beforeValidation');
        $object('beforeCreateValidation');
        $this->validateCreation($object);
        $this->strategy->create($object);
    }

    public function setProperty($key, $value)
    {
        $this->properties[$key] = $value;
        return $this;
    }

    public function toStoreString($name, $arguments): string
    {
        return 'Validating(' . $this->strategy->toStoreString() . ')';
    }

    /**
     * @param \Sayla\Objects\DataObject $object
     */
    public function update(DataObject $object)
    {
        $object('beforeValidation');
        $object('beforeUpdateValidation');
        $this->validateModification($object);
        $this->strategy->update($object);
    }

    public function validateCreation(DataObject $object): void
    {
        $this->getValidationBuilder()
            ->setRules($this->getCreateRules())
            ->validate($object->toArray());
    }

    public function validateDeletion(DataObject $object): void
    {
        $this->getValidationBuilder()
            ->setRules($this->getDeleteRules())
            ->validate($object->toArray());
    }

    public function validateModification(DataObject $object): void
    {
        $this->getValidationBuilder()
            ->setRules($this->getUpdateRules())
            ->validate($object->toArray());
    }
}