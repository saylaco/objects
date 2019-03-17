<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Objects\Transformers\Transformer;
use Sayla\Util\Mixin\Mixin;

class TransformationDescriptorMixin implements Mixin
{
    private $transformations;
    private $transformer;
    /** @var \Sayla\Objects\Transformers\ValueTransformerFactory */
    private $valueFactory;

    /**
     *  constructor.
     * @param $transformations
     */
    public function __construct(array $transformations)
    {
        $this->transformations = $transformations;
    }

    /**
     * @return \Sayla\Objects\Transformers\Transformer
     */
    public function getTransformer(array $excludedAttributes = null): \Sayla\Objects\Transformers\Transformer
    {
        if (empty($excludedAttributes)) {
            if (!isset($this->transformer)) {
                $this->transformer = new Transformer($this->transformations);
            }
            $transformer = $this->transformer;
        } else {
            $transformer = new Transformer(array_except($this->transformations, $excludedAttributes));
        }

        if (isset($this->valueFactory)) {
            $transformer->setFactory($this->valueFactory);
        }
        return $transformer;
    }

    /**
     * @param \Sayla\Objects\Transformers\ValueTransformerFactory $valueFactory
     */
    public function setValueFactory(\Sayla\Objects\Transformers\ValueTransformerFactory $valueFactory): void
    {
        $this->valueFactory = $valueFactory;
    }
}