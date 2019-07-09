<?php namespace Sayla\Objects\Transformers\Transformer;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Sayla\Objects\Transformers\SmashesToList;
use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class JsonListTransformer extends JsonTransformer implements SmashesToList, ValueTransformer
{
    use ValueTransformerTrait;

    public function getVarType(): string
    {
        return parent::getVarType() . '|' . $this->getClassName();
    }

    protected function prepareSmashedValue($value)
    {
        $output = $value;
        if ($value instanceof Collection) {
            return $value->values();
        }
        if (is_array($value)) {
            return array_values($value);
        }
        if (is_iterable($value)) {
            return collect($value)->values();
        }
        return Arr::wrap($output);
    }
}