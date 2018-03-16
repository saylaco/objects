<?php namespace Sayla\Objects\Transformers\Transformer;

use Illuminate\Contracts\Support\Arrayable;
use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;
use Sayla\Util\JsonHelper;

class ArrayTransformer implements ValueTransformer
{
    use ValueTransformerTrait;

    /**
     * @param mixed $value
     * @return array
     */
    public function build($value)
    {
        if (empty($value)) {
            return [];
        } elseif (is_string($value)) {
            return JsonHelper::decode($value, 1);
        }
        return (array)$value;
    }

    public function getScalarType(): ?string
    {
        return null;
    }

    /**
     * @param mixed $value
     * @return array
     */
    public function smash($value)
    {
        if ($value instanceof Arrayable) {
            return $value->toArray();
        }
        if (empty($value)) {
            return [];
        }
        return (array)$value;
    }
}