<?php namespace Sayla\Objects\Transformers\Transformer;

use Sayla\Objects\DataObject;
use Sayla\Objects\ObjectCollection;
use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;
use Sayla\Util\JsonHelper;

class ObjectCollectionTransformer implements ValueTransformer
{
    use ValueTransformerTrait;

    /**
     * @param mixed $value
     * @return \Sayla\Objects\ObjectCollection
     */
    public function build($value): ObjectCollection
    {
        $collection = $this->newCollectionInstance();
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        if (!is_null($value)) {
            $collection->makeObjects($value);
        }
        return $collection;
    }

    public function getScalarType(): ?string
    {
        return null;
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function smash($value): ?string
    {
        if ($value instanceof ObjectCollection) {
            return $value->toJson();
        }
        return JsonHelper::encode($value);
    }

    /**
     * @return mixed|object|\Sayla\Objects\ObjectCollection|static
     */
    protected function newCollectionInstance()
    {
        if ($this->options->resolver != null) {
            $collection = call_user_func($this->options->resolver);
        } elseif ($this->options->class != null) {
            $collection = (new \ReflectionClass($this->options->class))->newInstance();
        } else {
            $descriptor = $this->options->descriptor ?? $this->options->itemClass ?? DataObject::class;
            $collection = ObjectCollection::makeObjectCollection(
                $descriptor,
                $this->options->get('nullableItems', false),
                $this->options->get('requireKeys', false)
            );
        }
        return $collection;
    }
}