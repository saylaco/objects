<?php

namespace Sayla\Objects\Transformers\Transformer;

use Sayla\Objects\AttributableObject;
use Sayla\Objects\BaseDataModel;
use Sayla\Objects\DataObject;
use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class ObjectTransformer implements ValueTransformer
{
    use ValueTransformerTrait;

    /**
     * @param mixed $value
     * @return string|null
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
        $descriptor = $this->options->descriptor ?? $this->options->class;
        if ($this->options->hydrate) {
            return BaseDataModel::hydrateObject($descriptor, $attributes);
        }
        return DataObject::makeObject($descriptor, $attributes);
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