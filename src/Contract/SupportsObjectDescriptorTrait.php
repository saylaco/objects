<?php

namespace Sayla\Objects\Contract;

use Sayla\Objects\DataType\DataTypeDescriptor;

/**
 * Trait SupportsObjectDescriptorTrait
 * @mixin SupportsObjectDescriptor
 * @mixin \Sayla\Objects\Contract\SupportsDataTypeManager
 */
trait SupportsObjectDescriptorTrait
{
    use SupportsDataTypeManagerTrait;
    private $dataTypeName;

    final public function dataType(): DataType
    {
        return self::getDataTypeManager()->get($this->getDataType());
    }

    public function getDataType(): string
    {
        return $this->dataTypeName ?? static::class;
    }

    final public function descriptor(): DataTypeDescriptor
    {
        return self::getDataTypeManager()->getDescriptor($this->getDataType());
    }

    public function setDataType(string $dataType)
    {
        $this->dataTypeName = $dataType;
    }
}