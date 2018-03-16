<?php namespace Sayla\Objects\Transformers\Transformer;

use Illuminate\Support\Collection;
use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class CollectionTransformer implements ValueTransformer
{
    use ValueTransformerTrait;

    /**
     * @param mixed $value
     * @return string|null
     */
    public function build($value)
    {
        $collectionClass = $this->options->class ?: Collection::class;
        if (empty($value)) {
            $collection = new $collectionClass;
        } elseif (is_array($value)) {
            $collection = new $collectionClass($value);
        } elseif ($value instanceof $collectionClass) {
            $collection = $value;
        } elseif ($value instanceof Collection) {
            $collection = new $collectionClass($value->all());
        } else $collection = $value;
        if ($this->options->indexBy) {
            return $collection->keyBy($this->options->indexBy);
        }
        return $collection;
    }

    public function getScalarType(): ?string
    {
        return 'json';
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function smash($value)
    {

        if ($value instanceof Collection) {
            switch ($this->options->smashTo) {
                case 'items':
                    return $value->all();
                case 'json':
                    return $value->toJson();
                case 'array':
                default:
                    return $value->toArray();
            }
        }
        return $value;
    }
}