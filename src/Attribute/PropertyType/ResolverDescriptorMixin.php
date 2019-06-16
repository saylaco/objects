<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Contract\Attributes\AttributeResolver;
use Sayla\Objects\Exception\AttributeResolverNotFound;
use Sayla\Objects\ObjectCollection;
use Sayla\Util\Mixin\Mixin;

class ResolverDescriptorMixin implements Mixin
{
    /** @var \Illuminate\Support\Collection */
    protected $properties = [];
    /** @var string */
    private $dataType;

    public function __construct(array $properties, string $dataType)
    {
        $this->properties = collect($properties);
        $this->dataType = $dataType;
    }

    public function getResolvable()
    {
        return $this->properties->keys()->all();
    }

    /**
     * @param string $attributeName
     * @return \Sayla\Objects\Contract\AttributeResolver
     * @throws \Sayla\Objects\Exception\AttributeResolverNotFound
     */
    public function getResolver(string $attributeName): AttributeResolver
    {
        if (!isset($this->properties[$attributeName])) {
            throw new AttributeResolverNotFound('Resolver not found for ' . $this->dataType . '.$' . $attributeName);
        }
        /** @var \Sayla\Objects\Contract\AttributeResolver $resolver */
        $resolver = $this->properties[$attributeName]['delegate'];
        return $resolver;
    }

    public function hasResolver(string $attributeName)
    {
        return isset($this->properties[$attributeName]);
    }

    /**
     * @param string $attributeName
     * @param \Sayla\Objects\ObjectCollection|iterable $objects
     * @return array
     * @throws \Sayla\Objects\Exception\HydrationError
     */
    public function resolveValues(string $attributeName, iterable $objects)
    {
        $resolver = $this->getResolver($attributeName);
        return $resolver->resolveMany($objects);
    }
}
