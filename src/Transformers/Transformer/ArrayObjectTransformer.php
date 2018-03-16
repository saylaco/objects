<?php namespace Sayla\Objects\Transformers\Transformer;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Sayla\Data\DotArray;
use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;
use Sayla\Util\JsonHelper;

class ArrayObjectTransformer implements ValueTransformer
{
    use ValueTransformerTrait;

    /**
     * @param mixed $value
     * @return DotArray
     */
    public function build($value): Arrayable
    {
        $arrayableClass = $this->getArrayableClass();
        if (empty($value)) {
            $value = new $arrayableClass();
        } elseif (is_string($value)) {
            $value = new $arrayableClass(JsonHelper::decode($value, 1));
        } elseif (is_array($value)) {
            $value = new $arrayableClass($value);
        }
        return $value;
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
        if ($value instanceof Arrayable) {
            return $value->toArray();
        }
        if ($value instanceof Jsonable) {
            return JsonHelper::decode($value->toJson(), 1);
        }
        if (empty($value)) {
            return [];
        }
        return $value;
    }

    public function getArrayableClass(): string
    {
        return $this->options->get('class', DotArray::class);
    }
}