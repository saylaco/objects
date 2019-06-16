<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Transformers\Transformer;
use Sayla\Objects\Transformers\TransformerFactory;
use Sayla\Util\Mixin\Mixin;

class TransformationDescriptorMixin implements Mixin
{
    private $transformations = [];
    private $transformer;
    /** @var \Sayla\Objects\Transformers\TransformerFactory */
    private $factory;

    /**
     * @param \Sayla\Objects\Attribute\Property[] $transformations
     */
    public function __construct(array $transformations)
    {
        foreach ($transformations as $attr => $transformation) {
            $this->transformations[$attr] = $transformation->getValue();
        }
    }

    /**
     * @return \Sayla\Objects\Transformers\Transformer
     */
    public function getTransformer(array $excludedAttributes = null): Transformer
    {
        if (empty($excludedAttributes)) {
            if (!isset($this->transformer)) {
                $this->transformer = new Transformer($this->transformations);
            }
            $transformer = $this->transformer;
        } else {
            $transformer = new Transformer(array_except($this->transformations, $excludedAttributes));
        }

        if (isset($this->factory)) {
            $transformer->setFactory($this->factory);
        }
        return $transformer;
    }

    /**
     * @param \Sayla\Objects\Transformers\TransformerFactory $valueFactory
     */
    public function setValueFactory(TransformerFactory $valueFactory): void
    {
        $this->factory = $valueFactory;
    }
}