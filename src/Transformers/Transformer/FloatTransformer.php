<?php namespace Sayla\Objects\Transformers\Transformer;

use Sayla\Objects\Transformers\SmashesToNumber;

class FloatTransformer extends IntTransformer implements SmashesToNumber
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