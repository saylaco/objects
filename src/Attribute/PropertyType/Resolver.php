<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Attribute\AttributePropertyType;
use Sayla\Objects\Attribute\Resolver\AliasResolver;
use Sayla\Objects\Attribute\Resolver\CallableResolver;
use Sayla\Objects\Attribute\Resolver\ResolverDelegate;
use Sayla\Objects\Contract\AttributeResolver;
use Sayla\Objects\Contract\ModifiesAttributeDescriptor;
use Sayla\Util\Mixin\Mixin;

class Resolver implements AttributePropertyType, ModifiesAttributeDescriptor
{
    const NAME = 'resolver';
    const RESOLVER_METHOD_PATTERN = '/\\bp\\w+\\s+static\\s+\\w+\\s+(resolve([\\w_]+)(?:Attribute|Attributes))\\b/ui';
    private static $methodCache = [];

    public static function getProviders(): array
    {
        return [
            self::PROVIDER_MIXIN => function (string $dataType, array $properties): Mixin {
                return new ResolverDescriptorMixin(array_filter($properties), $dataType);
            }
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPropertyValue(string $attributeName, array $value, string $attributeType): ?array
    {
        $resolver = $value['value'] ?? null;
        $config = ['delegate' => null, 'autoResolve' => boolval($resolver)];
        if (isset($resolver)) {
            $config['delegate'] = $this->normalizeResolver($attributeName, $resolver);
        }
        if ($config['delegate'] === null && isset($value['methods'][$attributeName])) {
            $singleResolver = null;
            $multipleResolver = null;

            foreach ($value['methods'][$attributeName] as $method) {
                if ($method['isMultiple']) {
                    $multipleResolver = $this->normalizeResolver($attributeName, $method['callable']);
                } else {
                    $singleResolver = $this->normalizeResolver($attributeName, $method['callable']);
                }
            }

            if ($multipleResolver && $singleResolver) {
                $singleResolver->setOwnerAttributeName($attributeName);
                $singleResolver->setOwnerObjectClass($value['objectClass']);
                $multipleResolver->setOwnerAttributeName($attributeName);
                $multipleResolver->setOwnerObjectClass($value['objectClass']);
                $resolver = new ResolverDelegate($singleResolver, $multipleResolver);
                $resolver->setOwnerAttributeName($attributeName);
                $resolver->setOwnerObjectClass($value['objectClass']);
                $config['delegate'] = $resolver;
            } else {
                $config['delegate'] = $singleResolver ?: $multipleResolver;
            }
        }
        return isset($config['delegate']) ? $config : null;
    }

    public function modifyDescriptor(array $config, array $normalizedProperties): ?array
    {
        if (!isset($normalizedProperties['map']['to']) || $normalizedProperties['map']['to'] === true) {
            if ($config['delegate'] instanceof AttributeResolver) {
                return ['map.to' => false];
            }
        }
        return null;
    }

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