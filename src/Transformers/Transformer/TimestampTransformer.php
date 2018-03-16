<?php namespace Sayla\Objects\Transformers\Transformer;

class TimestampTransformer extends IntTransformer
{

    public function getNumericValue($value)
    {
        return is_numeric($value) ? $value : strtotime($value);
    }
}