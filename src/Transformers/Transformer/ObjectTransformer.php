<?php

namespace Sayla\Objects\Transformers\Transformer;

use Illuminate\Contracts\Support\Jsonable;
use Sayla\Objects\AttributableObject;
use Sayla\Objects\Contract\DataObject\SupportsDataTypeManager;
use Sayla\Objects\Contract\DataObject\SupportsDataTypeManagerTrait;
use Sayla\Objects\DataObject;
use Sayla\Objects\Transformers\AttributeValueTransformer;
use Sayla\Objects\Transformers\SmashesToHashMap;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class ObjectTransformer implements AttributeValueTransformer, SupportsDataTypeManager, SmashesToHashMap
{
    use ValueTransformerTrait;
    use SupportsDataTypeManagerTrait;

    /**
     * @param mixed $value
     * @return mixed|null|\Sayla\Objects\DataObject
     * @throws \Sayla\Objects\Contract\Exception\HydrationError
     */
    public function build($value)
    {
        if ($value instanceof AttributableObject) {
            return $value;
        }
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        if (empty($value) && !$this->options->get('always')) {
            if ($this->options->allowNullOnEmpty) {
                return null;
            }
        }
        $attributes = $value ?: [];
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
        return 'string';
    }

    public function getVarType(): string
    {
        $dataType = $this->getDataType();
        if ($dataType === 'object' || empty($dataType)) {
            return DataObject::class;
        }
        if (!class_exists($dataType) && $dataType) {
            return $this->options->class ?: self::getDataTypeManager()->get($dataType)->getObjectClass();
        }
        return $dataType;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function smash($value)
    {
        if ($value instanceof Jsonable) {
            return $value->toJson();
        }
        if ($value instanceof AttributableObject) {
            return json_encode($value->jsonSerialize());
        }
        return json_encode($value);
    }
}