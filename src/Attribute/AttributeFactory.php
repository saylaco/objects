<?php

namespace Sayla\Objects\Attribute;

use Closure;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Sayla\Exception\Error;
use Sayla\Objects\Attribute\PropertyType\Map;
use Sayla\Objects\Attribute\PropertyType\PropertyTypeFactory;
use Sayla\Objects\Attribute\PropertyType\Resolver;
use Sayla\Objects\Contract\AttributeResolver;
use Sayla\Objects\Contract\Property as PropertyInterface;
use Sayla\Objects\Contract\PropertyType;
use Sayla\Util\Mixin\Mixin;
use Sayla\Util\Mixin\MixinSet;
use Throwable;

class AttributeFactory
{
    const DEFAULT_ATTRIBUTE_TYPE = 'serial';
    protected $storeDefault = false;
    /**
     * @var string
     */
    private $classFile;
    private $descriptors;
    private $normalized = false;
    /**
     * @var string
     */
    private $objectClass;
    /** @var callable[][] */
    private $providers = [];

    public function __construct(string $objectClass, array $definitions, string $classFile = null)
    {
        $this->objectClass = $objectClass;
        $this->descriptors = $definitions;
        $this->classFile = $classFile;
    }

    public function addMixins(MixinSet $mixinSet): MixinSet
    {
        collect($this->providers)
            ->where('providerType', AttributePropertyType::PROVIDER_MIXIN)
            ->map(function (array $propertyProvider) {
                $properties = $this->getProperties($propertyProvider['property']);
                return $propertyProvider['provider']($this->objectClass, $properties->toArray());
            })
            ->each(function (Mixin $mixin) use ($mixinSet) {
                $mixinSet->add($mixin);
            });
        return $mixinSet;
    }

    /**
     * @param string $name
     * @return \Sayla\Objects\Attribute\Attribute
     * @throws \Sayla\Exception\Error
     */
    public function getAttribute(string $name): Attribute
    {
        return $this->getAttributes()[$name];
    }

    /**
     * @return \Sayla\Objects\Attribute\Attribute[]|\Illuminate\Support\Collection
     * @throws \Sayla\Exception\Error
     */
    public function getAttributes(): Collection
    {
        if (!$this->normalized) {
            $this->descriptors = $this->parseAttributes($this->descriptors);
            $this->normalized = true;
        }
        return collect($this->descriptors);
    }

    /**
     * @param string $propertyType
     * @return \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[]
     * @throws \Sayla\Exception\Error
     */
    public function getDefinedProperties(string $propertyType)
    {
        return $this->getAttributes()
            ->map->filterByProperty($propertyType)
            ->map->getFirst()
            ->filter();
    }

    public function getExtractionPipeline(): Pipeline
    {
        $mapPipes = [];
        $pipes = [];
        collect($this->providers)
            ->where('providerType', AttributePropertyType::PROVIDER_EXTRACTION)
            ->each(function (array $propertyProvider) use (&$mapPipes, &$pipes) {
                if ($propertyProvider['property'] === Map::NAME) {
                    $mapPipes[] = $propertyProvider['provider'];
                } else {
                    $pipes[] = $propertyProvider['provider'];
                }
            });
        $pipeline = new Pipeline();
        return $pipeline->through(array_merge($mapPipes, $pipes));
    }

    public function getHydrationPipeline(): Pipeline
    {
        $mapPipes = [];
        $pipes = [];
        collect($this->providers)
            ->where('providerType', AttributePropertyType::PROVIDER_HYDRATION)
            ->each(function (array $propertyProvider) use (&$mapPipes, &$pipes) {
                if ($propertyProvider['property'] === Map::NAME) {
                    $mapPipes[] = $propertyProvider['provider'];
                } else {
                    $pipes[] = $propertyProvider['provider'];
                }
            });
        $pipeline = new Pipeline();
        return $pipeline->through(array_merge($mapPipes, $pipes));
    }

    public function getMixins(): MixinSet
    {
        return $this->addMixins(new MixinSet());
    }

    /**
     * @return string[]
     * @throws \Sayla\Exception\Error
     */
    public function getNames(): array
    {
        return $this->getAttributes()->keys()->all();
    }

    /**
     * @return string
     */
    public function getObjectClass(): string
    {
        return $this->objectClass;
    }

    /**
     * @param string $propertyType
     * @return \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[]
     * @throws \Sayla\Exception\Error
     */
    public function getProperties(string $propertyType)
    {
        return $this->getAttributes()
            ->map->filterByProperty($propertyType)
            ->map->getFirst();
    }

    /**
     * @param \Sayla\Objects\Contract\PropertyType $type
     * @param $attributeName
     * @param $attributeType
     * @param $propertyValue
     * @return \Sayla\Objects\Attribute\Property
     */
    protected function makeProperty(PropertyType $type, $attributeName, $attributeType,
                                    $propertyValue): ?PropertyInterface
    {
        $value = $type->getPropertyValue($attributeName, $propertyValue, $attributeType, $this->objectClass);
        if ($value === null) {
            return null;
        }
        if (!$value instanceof PropertyInterface) {
            if (is_array($value)) {
                return new PropertySet($type->getName(), $value);
            } else {
                return new Property($type->getName(), $value);
            }
        }
        return $value;
    }

    /**
     * @param callable|array $definition
     * @param $attributeName
     * @return array
     */
    protected function normalize($definition, $attributeName): array
    {
        $descriptor = [];
        if ($definition instanceof Closure || !is_array($definition) || $definition instanceof AttributeResolver) {
            $descriptor[Resolver::NAME] = $definition;
        } else {
            foreach ($definition as $k => $v) {
                array_set($descriptor, $k, $v);
            }
        }
        if (isset($descriptor[Resolver::NAME])
            && $descriptor[Resolver::NAME] instanceof AttributeResolver) {
            $descriptor['map']['to'] = false;
        }
        if (str_contains($attributeName, ':')) {
            [$normalizedName, $normalizedType] = explode(':', trim($attributeName), 2);
        } else {
            if (!isset($normalizedName)) {
                $normalizedName = $attributeName;
            }
            $normalizedType = $descriptor['type'] ?? self::DEFAULT_ATTRIBUTE_TYPE;
        }
        return [$normalizedName, $normalizedType, $descriptor];
    }

    protected function parseAttributes(array $descriptors): array
    {
        $this->providers = [];
        $propertyTypes = [];

        # normalize short hand definitions that do not have properties
        $definitionsWithoutProperties = array_filter(array_keys($descriptors), 'is_int');
        if (count($definitionsWithoutProperties) > 0) {
            foreach ($definitionsWithoutProperties as $index) {
                $attributeKey = $descriptors[$index];
                unset($descriptors[$index]);
                if (!isset($descriptors[$attributeKey])) {
                    $descriptors[$attributeKey] = [];
                } else {
                    $attrName = trim(str_before($attributeKey, ':'));
                    if (isset($descriptors[$attrName])
                        && !isset($descriptors[$attrName]['type'])
                    ) {
                        $descriptors[$attrName]['type'] = trim(str_after($attributeKey, ':'));
                    }
                }
            }
        }
        $normalizedAttrs = [];
        foreach ($descriptors as $i => $descriptorData) {
            [$attributeName, $attributeType, $descriptorData] = $this->normalize($descriptorData, $i);
            $normalizedAttrs[] = compact('attributeName', 'attributeType', 'descriptorData');
            array_walk($descriptorData, function ($v, $k) use (&$propertyTypes, $attributeName) {
                $propertyTypes[str_before($k, '.')][$attributeName] = $attributeName;
            });
        }

        $converter = new PropertyTypeFactory();
        foreach ($converter->getProviders(array_keys($propertyTypes)) as $propertyType => $providers) {
            foreach ($providers as $providerType => $provider) {
                $this->providers[] = [
                    'providerType' => $providerType,
                    'attributes' => $propertyTypes[$propertyType] ?? [],
                    'property' => $propertyType,
                    'provider' => $provider,
                ];
            }
        }

        $normalizedDescriptors = [];
        # parse all attribute properties
        foreach ($normalizedAttrs as $attr) {
            $attributeName = $attr['attributeName'];
            try {
                $properties = $converter->getProperties(
                    $this->objectClass,
                    $this->classFile,
                    $attr['attributeName'],
                    $attr['attributeType'],
                    $attr['descriptorData']
                );
                $descriptor = new Attribute($attr['attributeType'], $attr['attributeName'], $properties);
                $normalizedDescriptors[$attr['attributeName']] = $descriptor;
            } catch (Throwable $e) {
                throw new Error("Could not build definitions descriptor for \${$attributeName}", $e);
            }
        }
        return $normalizedDescriptors;
    }
}