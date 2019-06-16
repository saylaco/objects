<?php

namespace Sayla\Objects\Transformers\Transformer;

use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class NumberTransformer implements ValueTransformer
{
    use ValueTransformerTrait;

    /**
     * @param mixed $value
     * @return string|null
     */
    public function build($value)
    {
        return blank($value) ? null : $this->getNumericValue($value);
    }

    /**
     * @param $value
     * @return float
     */
    public function getNumericValue($value)
    {
        if (is_float($value)) {
            return floatval($value);
        }
        return intval($value);
    }

    public function getScalarType(): string
    {
        return 'float';
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function smash($value)
    {
        return blank($value) ? null : $this->getNumericValue($value);
    }

}