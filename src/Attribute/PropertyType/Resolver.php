<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Attribute\Resolver\CallableResolver;
use Sayla\Objects\Attribute\Resolver\ResolverDelegate;
use Sayla\Objects\Contract\Attributes\AssociationResolver;
use Sayla\Objects\Contract\Attributes\AttributeResolver;
use Sayla\Objects\Contract\DataObject\ProvidesResolvers;
use Sayla\Objects\Contract\PropertyTypes\AttributePropertyType;
use Sayla\Objects\Contract\PropertyTypes\ModifiesAttributeDescriptor;
use Sayla\Objects\Contract\PropertyTypes\NormalizesPropertyValue;
use Sayla\Objects\DataType\DataTypeManager;
use Sayla\Util\Mixin\Mixin;
use Throwable;

class Resolver implements AttributePropertyType, ModifiesAttributeDescriptor, NormalizesPropertyValue
{
    const NAME = 'resolver';
    const RESOLVER_METHOD_PATTERN = '/\\bpublic\\s+static\\s+\\w+\\s+(resolve([\\w_]+)(?:Attribute|Attributes))\\b/ui';
    private static $methodCache = [];

    public static function getProviders(): array
    {
        return [
            self::PROVIDER_DESCRIPTOR_MIXIN => function (string $dataType, array $properties): Mixin {
                return new ResolverDescriptorMixin(array_filter($properties), $dataType);
            },
//            self::PROVIDER_HYDRATION => function ($context, callable $next) {
//                $attributes = $context->attributes;
//                $pruned = $context->descriptor->pruneResolvable($attributes);
//                $context->attributes = $pruned;
//                return $next($context);
//            },
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPropertyValue(string $attributeName, array $value, string $attributeType): ?array
    {
        $config = ['resolver' => $value['value'] ?? null, 'autoResolve' => boolval($value['autoResolve'] ?? false)];
        if (isset($config['resolver'])) {
            $config['resolver'] = $this->normalizeResolver($config['resolver']);
        } else if ($config['resolver'] === null && isset($value['methods'][$attributeName])) {
            $config['resolver'] = $this->getResolverFromObjectClass($value['objectClass'],
                $value['methods'][$attributeName], $attributeName);
        }
        return isset($config['resolver']) ? $config : null;
    }

    public function modifyDescriptor(array $config, array $normalizedProperties): ?array
    {
        $newProps = [];
        if (!isset($normalizedProperties['map']['to']) || $normalizedProperties['map']['to'] === true) {
            $resolver = $config['resolver'];
            if ($resolver instanceof AttributeResolver) {
                $newProps['map.to'] = false;
            }
            if ($resolver instanceof AssociationResolver) {
                $newProps['transform.type'] = 'object';
                try {
                    $varType = qualify_var_type(DataTypeManager::resolve()
                        ->getDescriptor($resolver->getAssociatedDataType())
                        ->getObjectClass());
                } catch (Throwable $throwable) {
                    $varType = qualify_var_type($resolver->getAssociatedDataType());
                }

                if (!$resolver->isSingular()) {
                    $varType .= '[]';
                    $newProps['transform.type'] = 'objectCollection';
                }
                $newProps['varType'] = $varType;
            }
        }
        return $newProps;
    }

    /**
     * @param array $descriptorData
     * @param string|ProvidesResolvers $objectClass
     * @param string|null $classFile
     * @return array|null
     */
    public function normalizePropertyValue(array $descriptorData, string $objectClass, ?string $classFile): ?array
    {
        if (isset($descriptorData['resolver'])) {
            $value = ['value' => $descriptorData['resolver'], 'methods' => [], 'objectClass' => $objectClass];
        } else {
            $value = ['methods' => [], 'value' => null, 'objectClass' => $objectClass];
        }
        if (!isset(self::$methodCache[$objectClass])) {
            self::$methodCache[$objectClass] = [];
            $matches = ($classFile
                ? preg_match_all(
                    self::RESOLVER_METHOD_PATTERN,
                    file_get_contents($classFile),
                    $methods,
                    PREG_SET_ORDER) > 0
                : false);
        }
        if (isset($matches, $methods)) {
            foreach ($methods as $methodMatch) {
                self::$methodCache[$objectClass][lcfirst($methodMatch[2])][] = [
                    'callable' => [$objectClass, $methodMatch[1]],
                    'isMultiple' => ends_with($methodMatch[1], 'Attributes')
                ];
            }
        }
        $value['methods'] = self::$methodCache[$objectClass];
        return $value;
    }

    /**
     * @param array $value
     * @param string $attributeName
     * @return \Sayla\Objects\Attribute\Resolver\ResolverDelegate|\Sayla\Objects\Contract\Attributes\AttributeResolver|null
     */
    private function getResolverFromObjectClass(string $objectClass, array $methods, string $attributeName)
    {
        $singleResolver = null;
        $multipleResolver = null;
        foreach ($methods as $method) {
            if ($method['isMultiple']) {
                $multipleResolver = $this->normalizeResolver($method['callable']);
            } else {
                $singleResolver = $this->normalizeResolver($method['callable']);
            }
        }

        if ($singleResolver) {
            $singleResolver->setOwnerAttributeName($attributeName);
            $singleResolver->setOwnerObjectClass($objectClass);
        }
        if ($multipleResolver) {
            $multipleResolver->setOwnerAttributeName($attributeName);
            $multipleResolver->setOwnerObjectClass($objectClass);
        }

        if ($multipleResolver && $singleResolver) {
            return new ResolverDelegate($singleResolver, $multipleResolver);
        }
        return $singleResolver ?: $multipleResolver;
    }

    private function normalizeResolver($resolver): AttributeResolver
    {
        if ($resolver instanceof AttributeResolver) {
            return $resolver;
        }
        return new CallableResolver($resolver);
    }
}