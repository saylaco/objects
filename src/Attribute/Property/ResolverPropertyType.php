<?php

namespace Sayla\Objects\Attribute\Property;

use Sayla\Exception\Error;
use Sayla\Objects\Attribute\Resolver\AliasResolver;
use Sayla\Objects\Attribute\Resolver\CallableResolver;
use Sayla\Objects\Attribute\Resolver\ResolverDelegate;
use Sayla\Objects\Contract\AttributeResolver;
use Sayla\Objects\Contract\PropertyType;
use Sayla\Objects\Contract\ProvidesDataTypeDescriptorMixin;
use Sayla\Objects\Exception\PropertyError;
use Sayla\Util\Mixin\Mixin;

class ResolverPropertyType implements PropertyType, ProvidesDataTypeDescriptorMixin
{

    public static function getHandle(): string
    {
        return 'resolver';
    }

    public function getDataTypeDescriptorMixin(string $dataType, array $properties): Mixin
    {
        return new ResolverDescriptorMixin(array_filter($properties), $dataType);
    }

    /**
     * @return string[]
     */
    public function getDefinitionKeys(): ?array
    {
        return ['autoResolve', self::getHandle()];
    }

    /**
     * @param \ReflectionClass $reflection
     * @param string $attributeName
     * @return \Sayla\Objects\Contract\AttributeResolver|null
     * @throws \ReflectionException
     * @throws \Sayla\Exception\Error
     */
    protected function getMultipleResolver(\ReflectionClass $reflection, string $attributeName): ?AttributeResolver
    {
        if ($reflection->hasMethod($method = 'resolve' . studly_case($attributeName) . 'Attributes')) {
            if (!$reflection->getMethod($method)->isStatic()) {
                throw new Error($reflection->name . '::' . $method . ' must be static');
            }
            return new CallableResolver($reflection->name . '::' . $method);
        }
        return null;
    }

    public function getName(): string
    {
        return self::getHandle();
    }

    public function getPropertyValue(string $attributeName, $propertyValue, string $attributeType, string $objectClass)
    {
        $config = ['delegate' => null, 'autoResolve' => boolval($propertyValue['autoResolve'])];
        if (isset($propertyValue['resolver'])) {
            if ($propertyValue['resolver'] instanceof \Closure) {
                $config['delegate'] = new CallableResolver($propertyValue['resolver']->bindTo(null, $objectClass));
            } else {
                $config['delegate'] = $this->normalizeResolver($attributeName, $propertyValue['resolver']);
            }
        } else {
            $reflection = new \ReflectionClass($objectClass);
            $singleResolver = $this->getSingleResolver($reflection, $attributeName);
            $multipleResolver = $this->getMultipleResolver($reflection, $attributeName) ?? $singleResolver;
            if ($multipleResolver && $singleResolver) {
                $singleResolver->setOwnerAttributeName($attributeName);
                $singleResolver->setOwnerObjectClass($objectClass);
                $multipleResolver->setOwnerAttributeName($attributeName);
                $multipleResolver->setOwnerObjectClass($objectClass);
                $resolver = new ResolverDelegate($singleResolver, $multipleResolver);
                $resolver->setOwnerAttributeName($attributeName);
                $resolver->setOwnerObjectClass($objectClass);
                $config['delegate'] = $resolver;
            }
        }
        if ($config['delegate'] === null) {
            return null;
        }
        return $config;
    }

    /**
     * @param \ReflectionClass $reflection
     * @param string $attributeName
     * @return null|\Sayla\Objects\Contract\AttributeResolver
     * @throws \Sayla\Objects\Exception\PropertyError
     */
    protected function getSingleResolver(\ReflectionClass $reflection, string $attributeName): ?AttributeResolver
    {
        if ($reflection->hasMethod($method = 'resolve' . studly_case($attributeName) . 'Attribute')) {
            if (!$reflection->getMethod($method)->isStatic()) {
                throw new PropertyError($reflection->name . '::' . $method . ' must be static');
            }
            return new CallableResolver($reflection->name . '::' . $method);
        }
        return null;
    }

    private function normalizeResolver($attributeName, $resolver): AttributeResolver
    {
        if ($resolver instanceof AttributeResolver) {
            return $resolver;
        }
        if (is_string($resolver) && starts_with($resolver, '@')) {
            // create a alias resolver:
            // @getRealValue => $object->getRealValue($attributeName)
            $alias = substr($resolver, 1) . '(' . var_str($attributeName) . ')';
            return new AliasResolver($alias);
        }
        return new CallableResolver($resolver);
    }
}