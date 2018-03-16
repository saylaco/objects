<?php namespace Sayla\Objects\Transformers\Transformer;

class DoubleTransformer extends IntTransformer
{
    /**
     * @param $value
     * @return float
     */
    public function getNumericValue($value)
    {
        return doubleval($value);
    }

}