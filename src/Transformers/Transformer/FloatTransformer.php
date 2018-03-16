<?php namespace Sayla\Objects\Transformers\Transformer;

class FloatTransformer extends IntTransformer
{
    /**
     * @param $value
     * @return float
     */
    public function getNumericValue($value)
    {
        return floatval($value);
    }

}