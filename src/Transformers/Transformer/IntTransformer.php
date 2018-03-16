<?php

namespace Sayla\Objects\Transformers\Transformer;

use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class IntTransformer implements ValueTransformer
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

    public function getScalarType(): string
    {
        return 'int';
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function smash($value)
    {
        return blank($value) ? null : $this->getNumericValue($value);
    }

    /**
     * @param $value
     * @return float
     */
    public function getNumericValue($value)
    {
        return intval($value);
    }

}