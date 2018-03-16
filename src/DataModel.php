<?php

namespace Sayla\Objects;

use Sayla\Objects\Contract\ValidatesSelf;
use Sayla\Objects\Inspection\ObjectDescriptor;
use Sayla\Objects\Validation\ValidatableTrait;

abstract class DataModel extends BaseDataModel implements ValidatesSelf
{
    use ValidatableTrait;

    protected function getValidationBuilderProperties(): array
    {
        return $this->toArray();
    }

    /**
     * @return \Sayla\Objects\Inspection\ObjectDescriptor
     */
    protected function getValidationDescriptor(): ObjectDescriptor
    {
        return $this->descriptor();
    }

}