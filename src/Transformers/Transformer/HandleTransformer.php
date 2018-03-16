<?php

namespace Sayla\Objects\Transformers\Transformer;

use Illuminate\Support\Str;
use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class HandleTransformer implements ValueTransformer
{
    use ValueTransformerTrait;

    /**
     * @param mixed $value
     * @return string|null
     */
    public function build($value)
    {
        return empty($value) ? null : Str::camel($value);
    }

    public function getScalarType(): ?string
    {
        return 'string';
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function smash($value)
    {
        return empty($value) ? null : Str::camel($value);
    }
}