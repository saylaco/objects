<?php

namespace Sayla\Objects\Transformers\Transformer;

use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class SerialTransformer implements ValueTransformer
{
    use ValueTransformerTrait;

    /**
     * @param mixed $value
     * @return string|null
     */
    public function build($value)
    {
        return unserialize($value);
    }

    public function getScalarType(): string
    {
        return 'string';
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function smash($value)
    {
        return serialize($value);
    }
}