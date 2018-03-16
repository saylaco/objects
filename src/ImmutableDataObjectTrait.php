<?php

namespace Sayla\Objects;

/**
 * @mixin \Sayla\Objects\AttributableObject
 */
trait ImmutableDataObjectTrait
{
    private $workingAttributes = [];

    /**
     * Fill the model with an array of attributes.
     *
     * @param iterable $attributes
     * @return $this
     */
    public function fill(iterable $attributes)
    {
        parent::fill($attributes);
        return $this->getDirtyObject();
    }

    /**
     * @return static
     */
    public function getDirtyObject()
    {
        $copy = clone $this;
        $copy->setAttributes(array_merge($this->toArray(), $this->workingAttributes));
        $copy->workingAttributes = [];
        return $copy;
    }

    /**
     * @param string $attribute
     */
    protected function removeAttribute(string $attribute)
    {
        if ($this->isMutable()) {
            parent::removeAttribute($attribute);
        } else {
            unset($this->workingAttributes[$attribute]);
        }
    }

    /**
     * @return bool
     */
    public function isMutable(): bool
    {
        return $this->isInitializing() || $this->isStoring() || $this->isResolving();
    }

    protected function resetDirty(): void
    {
        $this->workingAttributes = [];
    }

    /**
     * @param string $attributeName
     * @param $value
     */
    final protected function setAttributeValue(string $attributeName, $value): void
    {
        if ($this->isMutable()) {
            $this->forceSetAttributeValue($attributeName, $value);
        } else {
            $atts = $this->toArray();
            $this->setAttributes(array_merge($atts, $this->workingAttributes));
            $this->forceSetAttributeValue($attributeName, $value);
            $this->workingAttributes = $this->toArray();
            $this->setAttributes($atts);
        }
    }

    protected function forceSetAttributeValue(string $attributeName, $value)
    {
        parent::setAttributeValue($attributeName, $value);
    }

    /**
     * @return mixed[]
     */
    public function getModifiedAttributes(): array
    {
        return array_only($this->workingAttributes, $this->getModifiedAttributeNames());
    }
}