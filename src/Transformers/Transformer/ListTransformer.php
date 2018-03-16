<?php namespace Sayla\Objects\Transformers\Transformer;

class ListTransformer extends ArrayTransformer
{
    /**
     * @param mixed $value
     * @return array
     */
    public function build($value)
    {
        return array_values(parent::build($value));
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