<?php namespace Sayla\Objects\Transformers\Transformer;

use Sayla\Objects\Transformers\SmashesToNumber;

class DoubleTransformer extends IntTransformer implements SmashesToNumber
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