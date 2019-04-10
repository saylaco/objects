<?php

namespace Sayla\Objects\Transformers\Transformer;

use Sayla\Objects\AttributableObject;
use Sayla\Objects\Contract\SupportsDataTypeManager;
use Sayla\Objects\Contract\SupportsDataTypeManagerTrait;
use Sayla\Objects\Transformers\AttributeValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class ObjectTransformer implements AttributeValueTransformer, SupportsDataTypeManager
{
    use ValueTransformerTrait;
    use SupportsDataTypeManagerTrait;

    /**
     * @param mixed $value
     * @return mixed|null|\Sayla\Objects\DataObject
     * @throws \Sayla\Objects\Exception\HydrationError
     */
    public function build($value)
    {
        if ($value instanceof AttributableObject) {
            return $value;
        }
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        if (empty($value) && $this->options->allowNullOnEmpty) {
            return null;
        }
        $attributes = $value ?? [];
        $dataType = $this->getDataType();
        return self::getDataTypeManager()->get($dataType)->hydrate($attributes);
    }

    /**
     * @return mixed|null
     */
    protected function getDataType()
    {
        return $this->options->dataType ?? $this->options->class;
    }

    public function getScalarType(): ?string
    {
        return 'array';
    }

    public function getVarType(): string
    {
        $dataType = $this->getDataType();
        if (!class_exists($dataType)) {
            return $this->options->class ?: self::getDataTypeManager()->get($dataType)->getObjectClass();
        }
        return $dataType;
    }

    /**
     * @param mixed $value
     * @return array
     */
    public function smash($value)
    {
        if ($value instanceof AttributableObject) {
            return $value->jsonSerialize();
        }
        return (array)$value;
    }
}