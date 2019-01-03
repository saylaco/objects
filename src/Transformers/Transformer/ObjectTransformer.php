<?php

namespace Sayla\Objects\Transformers\Transformer;

use Sayla\Objects\AttributableObject;
use Sayla\Objects\Contract\SupportsDataTypeManager;
use Sayla\Objects\Contract\SupportsDataTypeManagerTrait;
use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class ObjectTransformer implements ValueTransformer, SupportsDataTypeManager
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
        $dataType = $this->options->dataType ?? $this->options->class;
        return self::getDataTypeManager()->get($dataType)->hydrate($attributes);
    }

    public function getScalarType(): ?string
    {
        return 'array';
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