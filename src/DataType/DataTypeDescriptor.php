<?php

namespace Sayla\Objects\DataType;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Sayla\Exception\Error;
use Sayla\Objects\Contract\Attributes\AssociationResolver;
use Sayla\Objects\Transformers\AttributeValueTransformer;
use Sayla\Objects\Transformers\SmashesToHashMap;
use Sayla\Objects\Transformers\SmashesToList;
use Sayla\Util\Mixin\MixinSet;
use Serializable;
use Throwable;

/**
 * Class DataTypeDescriptor
 * @mixin \Sayla\Objects\Attribute\PropertyType\AccessDescriptorMixin
 * @mixin \Sayla\Objects\Attribute\PropertyType\MapDescriptorMixin
 * @mixin \Sayla\Objects\Attribute\PropertyType\ResolverDescriptorMixin
 * @mixin \Sayla\Objects\Attribute\PropertyType\TransformationDescriptorMixin
 */
class DataTypeDescriptor implements Serializable
{
    /** @var \Sayla\Util\Mixin\MixinSet */
    protected $mixins;
    /** @var string[] */
    private $attributeNames = [];
    /** @var string */
    private $class;
    /** @var string */
    private $name;

    /**
     * DataTypeDescriptor constructor.
     * @param string $name
     * @param string $class
     * @param array $attributeNames
     * @param \Sayla\Util\Mixin\MixinSet|null $mixins
     */
    public function __construct(string $name, string $class, array $attributeNames, MixinSet $mixins = null)
    {
        $this->class = $class;
        $this->name = $name;
        $this->attributeNames = array_combine($attributeNames, $attributeNames);
        $this->setMixins($mixins ?? new MixinSet());
    }

    public function __call($name, $arguments)
    {
        return $this->mixins->call($name, $arguments);
    }

    public function getAttributeNames()
    {
        return $this->attributeNames;
    }

    public function getDataType(): string
    {
        return $this->name;
    }

    public function getMixin(string $name)
    {
        return $this->mixins[$name] ?? $this->mixins[class_basename($name)];
    }

    /**
     * @return \Sayla\Util\Mixin\MixinSet
     */
    public function getMixins(): MixinSet
    {
        return $this->mixins;
    }

    /**
     * @param \Sayla\Util\Mixin\MixinSet $mixins
     */
    public function setMixins(MixinSet $mixins): void
    {
        $this->mixins = $mixins;
    }

    public function getObjectClass(): string
    {
        return $this->class;
    }

    public function getVarTypes()
    {
        $varTypes = [];
        $transformer = $this->getTransformer();
        foreach ($this->getResolvable() as $attributeName) {
            $resolver = $this->getResolver($attributeName);
            if ($resolver instanceof AssociationResolver) {
                try {
                    $varType = qualify_var_type(DataTypeManager::resolve()
                        ->getDescriptor($resolver->getAssociatedDataType())
                        ->getObjectClass());
                } catch (Throwable $throwable) {
                    $varType = qualify_var_type($resolver->getAssociatedDataType());
                }

                if (!$resolver->isSingular()) {
                    $varType .= '[]';
                }

                $varTypes[$attributeName] = [$varType];
            }
        }

        foreach (Arr::sort($transformer->getAttributeNames()) as $attributeName) {
            try {
                $valueTransformer = $transformer->getValueTransformer($attributeName);
                $varType = $transformer->getAttributeOptions()[$attributeName]['varType'] ??
                    ($valueTransformer instanceof AttributeValueTransformer
                        ? $valueTransformer->getVarType()
                        : $valueTransformer->getScalarType() ?: 'mixed');
                $_varTypes = array_map('qualify_var_type', explode('|', $varType));
                if (!$valueTransformer instanceof AttributeValueTransformer
                    && ($valueTransformer instanceof SmashesToHashMap || $valueTransformer instanceof SmashesToList)) {
                    $isIterable = false;
                    foreach ($_varTypes as $varType)
                        if ($isIterable = Str::contains($varType, ['[', ']'])) {
                            break;
                        }
                    if (!$isIterable) {
                        $_varTypes[0] = Str::finish($_varTypes[0], '[]');
                    }
                };

            } catch (Error $exception) {
                $_varTypes = [$transformer->getAttributeOptions()[$attributeName]['type']];
            }
            $varTypes[$attributeName] = $_varTypes;
        }
        return $varTypes;
    }

    public function hasMixin(string $mixinClassOrName)
    {
        foreach ($this->mixins as $mixinName => $mixin)
            if ($mixinName === $mixinClassOrName || is_a($mixin, $mixinClassOrName)) {
                return true;
            }
        return false;
    }

    public function serialize()
    {
        $props = [
            'attributeNames' => $this->attributeNames,
            'class' => $this->class,
            'dataType' => $this->name,
            'mixins' => $this->mixins
        ];
        return serialize($props);
    }

    public function unserialize($serialized)
    {
        $props = unserialize($serialized);
        $this->attributeNames = $props['attributeNames'];
        $this->class = $props['class'];
        $this->name = $props['dataType'];
        $this->mixins = $props['mixins'];
    }
}