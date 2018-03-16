<?php namespace Sayla\Objects\Transformers\Transformer;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Sayla\Data\DotArray;
use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;
use Sayla\Util\JsonHelper;

class JsonTransformer implements ValueTransformer
{
    use ValueTransformerTrait;

    /**
     * @param mixed $value
     * @return DotArray
     */
    public function build($value)
    {
        $class = $this->options->get('class', DotArray::class);
        if (empty($value)) {
            return new $class();
        }
        if ($value instanceof $class) {
            return $value;
        }
        $constructorValue = $this->convertToArray($value);
        return new $class($constructorValue);
    }

    public function getScalarType(): string
    {
        return 'string';
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function smash($value)
    {
        $output = $value;
        if ($output instanceof Jsonable) {
            $output = $output->toJson();
        } elseif ($output instanceof \JsonSerializable) {
            $output = JsonHelper::encode($output);
        } elseif ($output instanceof Arrayable) {
            $output = JsonHelper::encode($output->toArray());
        } elseif ($value == '' || $value === null) {
            $output = '{}';
        } elseif (!is_string($value)) {
            $output = JsonHelper::encode($output ?: []);
        }
        return $output;
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function convertToArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if ($value == '' || $value === null) {
            return [];
        }
        if (is_string($value)) {
            $constructorValue = JsonHelper::decode($value, 1);
        } else {
            $constructorValue = $value;
        }
        return $constructorValue;
    }
}