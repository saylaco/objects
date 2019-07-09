<?php namespace Sayla\Objects\Transformers\Transformer;

use Sayla\Objects\Transformers\SmashesToList;

class ListTransformer extends ArrayTransformer implements SmashesToList
{
    /**
     * @param mixed $value
     * @return array
     */
    public function build($value)
    {
        return array_values(parent::build($value));
    }

    public function getScalarType(): ?string
    {
        return 'array';
    }

    /**
     * @param mixed $value
     * @return array
     */
    public function smash($value)
    {
        return array_values(parent::build($value));
    }
}