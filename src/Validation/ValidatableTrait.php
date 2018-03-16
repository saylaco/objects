<?php

namespace Sayla\Objects\Validation;

use Sayla\Objects\Inspection\ObjectDescriptor;

trait ValidatableTrait
{
    private $skipValidation = false;

    public function create()
    {
        if (!$this->skipValidation) {
            $this->validateCreation();
        }
        return parent::create();
    }

    public function validateCreation(): void
    {
        $this->getValidationBuilder()
            ->setRules($this->getCreateRules())
            ->validate($this->toArray());
    }

    /**
     * @return ValidationBuilder
     */
    public function getValidationBuilder()
    {
        $attributesDescriptor = $this->getValidationDescriptor();
        $properties = $this->getValidationBuilderProperties();
        $builder = (new ValidationBuilder(class_basename($attributesDescriptor->getObjectClass()), $properties))
            ->setMessages($attributesDescriptor->validationMessages)
            ->setCustomAttributes($attributesDescriptor->labels);
        return $builder;
    }

    /**
     * @return array
     */
    public function getCreateRules(): array
    {
        return array_merge_recursive($this->getValidationDescriptor()->rules,
            $this->getValidationDescriptor()->createRules);
    }

    public function delete()
    {
        if (!$this->skipValidation) {
            $this->validateDeletion();
        }
        return parent::delete();
    }

    public function validateDeletion(): void
    {
        $this->getValidationBuilder()
            ->setRules($this->getDeleteRules())
            ->validate($this->toArray());
    }

    /**
     * @return array
     */
    public function getDeleteRules(): array
    {
        return array_merge_recursive($this->getValidationDescriptor()->rules,
            $this->getValidationDescriptor()->deleteRules);
    }

    protected abstract function getValidationBuilderProperties(): array;

    /**
     * @return \Sayla\Objects\Inspection\ObjectDescriptor
     */
    protected function getValidationDescriptor(): ObjectDescriptor
    {
        return $this->descriptor;
    }

    public function unsafeCreate()
    {
        $this->skipValidation = true;
        $result = $this->create();
        $this->skipValidation = false;
        return $result;
    }

    public function unsafeDelete()
    {
        $this->skipValidation = true;
        $result = $this->delete();
        $this->skipValidation = false;
        return $result;
    }

    public function unsafeUpdate()
    {
        $this->skipValidation = true;
        $result = $this->update();
        $this->skipValidation = false;
        return $result;
    }

    public function update()
    {
        if (!$this->skipValidation) {
            $this->validateModification();
        }
        return parent::update();
    }

    public function validateModification(): void
    {
        $this->getValidationBuilder()
            ->setRules($this->getUpdateRules())
            ->validate($this->toArray());
    }

    /**
     * @return array
     */
    public function getUpdateRules(): array
    {
        return array_merge_recursive($this->getValidationDescriptor()->rules,
            $this->getValidationDescriptor()->updateRules);
    }
}