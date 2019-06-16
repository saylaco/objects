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
     * @return \Sayla\Objects\Contract\Attributes\AttributeResolver
     * @throws \Sayla\Objects\Exception\AttributeResolverNotFound
     */
    public function getResolver(string $attributeName): AttributeResolver
    {
        if (!isset($this->properties[$attributeName])) {
            throw new AttributeResolverNotFound('Resolver not found for ' . $this->dataType . '.$' . $attributeName);
        }
        /** @var \Sayla\Objects\Contract\Attributes\AttributeResolver $resolver */
        $resolver = $this->properties[$attributeName]['resolver'];
        return $resolver;
    }

    public function hasResolver(string $attributeName)
    {
        return isset($this->properties[$attributeName]);
    }

    public function pruneResolvable($data)
    {
        foreach (array_only($data, $this->getResolvable()) as $attributeName => $value) {
            if ($value === null) {
                unset($data[$attributeName]);
            }
        }
        return $data;
    }

    /**
     * @param \Sayla\Objects\ObjectCollection $collection
     * @param array $attributeNames
     * @return \Sayla\Objects\ObjectCollection
     * @throws \Sayla\Objects\Exception\AttributeResolverNotFound
     */
    public function resolve(ObjectCollection $collection, array $attributeNames)
    {
        $allAttributes = [];
        // get values with resolvers
        $resolveViaResolvers = collect($attributeNames)->filter(function ($attributeName) {
            return $this->hasResolver($attributeName);
        });

        foreach ($resolveViaResolvers as $attribute) {
            $resolver = $this->getResolver($attribute);
            $values = $resolver->resolveMany($collection);
            foreach ($values as $i => $value) {
                $allAttributes[$i][$attribute] = $value;
            }
        }
        if (!empty($allAttributes)) {
            foreach ($collection as $i => $object) {
                $object->init($allAttributes[$i]);
            }
        }
        return $collection;
    }
}
