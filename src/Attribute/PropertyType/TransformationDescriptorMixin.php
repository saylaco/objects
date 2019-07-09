<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Transformers\Transformer;
use Sayla\Objects\Transformers\TransformerFactory;
use Sayla\Util\Mixin\Mixin;

class TransformationDescriptorMixin implements Mixin
{
    private $createTransformations = [];
    /** @var \Sayla\Objects\Transformers\TransformerFactory */
    private $factory;
    private $transformations = [];
    private $transformers = [];
    private $updateTransformations = [];

    /**
     * @param \Sayla\Objects\Attribute\Property[] $transformations
     */
    public function __construct(array $transformations)
    {
        foreach ($transformations as $attr => $transformation) {
            $options = $transformation->getValue();
            unset($options['onCreate']);
            unset($options['onUpdate']);

            if (isset($transformation['onCreate']) && $transformation['onCreate']) {
                $this->createTransformations[$attr] = array_merge($options, ['always' => true]);
            }
            if (isset($transformation['onUpdate']) && $transformation['onUpdate']) {
                $this->updateTransformations[$attr] = array_merge($options, ['always' => true]);
            }
            $this->transformations[$attr] = $options;
        }
    }

    /**
     * @return \Sayla\Objects\Transformers\Transformer
     */
    public function getOnCreateTransformer(): Transformer
    {
        return $this->transformers['create']
            ?? $this->transformers['create'] = $this->makeTransformer($this->createTransformations, null);
    }

    /**
     * @return \Sayla\Objects\Transformers\Transformer
     */
    public function getOnUpdateTransformer(): Transformer
    {
        return $this->transformers['update']
            ?? $this->transformers['update'] = $this->makeTransformer($this->updateTransformations, null);
    }

    /**
     * @return string[]
     */
    public function getTransformable(): array
    {
        return array_keys($this->transformations);
    }

    /**
     * @return \Sayla\Objects\Transformers\Transformer
     */
    public function getTransformer(array $excludedAttributes = null): Transformer
    {
        return $this->makeTransformer($this->transformations, $excludedAttributes);
    }

    /**
     * @param \Sayla\Objects\Transformers\TransformerFactory $valueFactory
     */
    public function setTransformerFactory(TransformerFactory $valueFactory): void
    {
        $this->factory = $valueFactory;
    }

    /**
     * @param array $transformations
     * @param array|null $excludedAttributes
     * @return \Sayla\Objects\Transformers\Transformer
     */
    private function makeTransformer(array $transformations, ?array $excludedAttributes): Transformer
    {
        if (empty($excludedAttributes)) {
            $transformer = new Transformer($transformations);
        } else {
            $transformer = new Transformer(array_except($transformations, $excludedAttributes));
        }

        if (isset($this->factory)) {
            $transformer->setFactory($this->factory);
        }
        return $transformer;
    }
}