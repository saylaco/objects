<?php

namespace Sayla\Objects\Transformers;


trait ValueTransformerTrait
{
    /** @var  Options */
    protected $options;

    /**
     * @param \Sayla\Objects\Transformers\Options $options
     */
    public function setOptions(Options $options): void
    {
        $this->options = $options;
    }
}