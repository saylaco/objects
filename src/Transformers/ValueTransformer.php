<?php

namespace Sayla\Objects\Transformers;


interface ValueTransformer
{
    /**
     * @param mixed $value
     * @return mixed
     */
    public function build($value);

    /**
     * @return null|string
     */
    public function getScalarType(): ?string;

    /**
     * @param \Sayla\Objects\Transformers\Options $options
     * @return mixed
     */
    public function setOptions(Options $options);

    /**
     * @param mixed $value
     * @return mixed
     */
    public function smash($value);
}