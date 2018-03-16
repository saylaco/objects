<?php

namespace Sayla\Objects\Transformers\Transformer;

use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class FakeTransformer implements ValueTransformer
{
    use ValueTransformerTrait;

    /**
     * @param mixed $value
     * @return string|null
     */
    public function build($value)
    {
        return null;
    }

    public function getScalarType(): ?string
    {
        // TODO: Implement getScalarType() method.
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function smash($value)
    {
        return null;
    }
}