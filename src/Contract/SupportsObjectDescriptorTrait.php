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


    public static function getDefinedDataType(): string
    {
        return static::DATA_TYPE ?? static::class;
    }

    final static public function getDescriptor(): DataTypeDescriptor
    {
        return self::getDataTypeManager()->getDescriptor(static::getDefinedDataType());
    }

    final public function dataType(): DataType
    {
        return self::getDataTypeManager()->get($this->getDataType());
    }

    final public function descriptor(): DataTypeDescriptor
    {
        return self::getDataTypeManager()->getDescriptor($this->getDataType());
    }

    public function getDataType(): string
    {
        return $this->dataTypeName ?? static::getDefinedDataType();
    }

    public function setDataType(string $dataType)
    {
        $this->dataTypeName = $dataType;
    }
}