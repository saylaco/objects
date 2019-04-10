<?php namespace Sayla\Objects\Transformers\Transformer;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Sayla\Data\DotArrayObject;
use Sayla\Objects\Transformers\AttributeValueTransformer;
use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;
use Sayla\Util\JsonHelper;

class ArrayObjectTransformer implements ValueTransformer, AttributeValueTransformer
{
    use ValueTransformerTrait;

    /**
     * @param mixed $value
     * @return DotArrayObject
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

    public function getArrayableClass(): string
    {
        return $this->options->get('class', DotArrayObject::class);
    }

    public function getScalarType(): ?string
    {
        return 'array';
    }

    public function getVarType(): string
    {
        return $this->getArrayableClass();
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
}