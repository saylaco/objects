<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Illuminate\Support\Str;
use Sayla\Objects\Contract\PropertyTypes\AttributePropertyType;
use Sayla\Objects\Contract\PropertyTypes\DefinesOptions;
use Sayla\Objects\Contract\PropertyTypes\ModifiesAttributeDescriptor;
use Sayla\Objects\Contract\PropertyTypes\NormalizesPropertyValue;
use Sayla\Util\Mixin\Mixin;

class Map implements AttributePropertyType, DefinesOptions, ModifiesAttributeDescriptor, NormalizesPropertyValue
{
    use SupportAutoAnnotationTrait;
    const CASES = ['studly', 'camel', 'snake', null];
    const NAME = 'map';
    /**
     * @var array
     */
    private $options;

    public static function defineOptions($resolver)
    {
        $resolver->setDefault('case', null);
        $resolver->setAllowedValues('case', self::CASES);

        $resolver->setDefault('toCase', null);
        $resolver->setAllowedValues('toCase', self::CASES);

        $resolver->setDefault('fromCase', null);
        $resolver->setAllowedValues('fromCase', self::CASES);
    }

    public static function getProviders(): array
    {
        return [
            self::PROVIDER_HYDRATION => function ($context, callable $next) {
                $context->attributes = $context->descriptor->hydrate($context->attributes);
                return $next($context);
            },
            self::PROVIDER_EXTRACTION => function ($context, callable $next) {
                $context->attributes = $context->descriptor->extract($context->attributes);
                return $next($context);
            },
            self::PROVIDER_DESCRIPTOR_MIXIN => function (string $dataType, array $properties): Mixin {
                return new MapDescriptorMixin($properties);
            }
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPropertyValue(string $attributeName, array $value, string $attributeType): ?array
    {
        if ($value['from'] === false && $value['to'] === false) {
            return null;
        }
        if ($value['to'] === true) {
            $value['to'] = $attributeName;
        }
        if ($value['from'] === true) {
            $value['from'] = $value['to'];
        }
        return [
            'attribute' => $attributeName,
            'to' => $this->applyCasing('toCase', $value['to']),
            'from' => $this->applyCasing('fromCase', $value['from']),
        ];
    }

    public function modifyDescriptor(array $propertyValue, array $normalizedProperties): ?array
    {
        if (!isset($normalizedProperties['transform']['alias'])
            && $propertyValue['to'] !== $propertyValue['attribute']) {
            return ['transform.alias' => $propertyValue['to']];
        }
        return null;
    }

    public function normalizePropertyValue(array $descriptorData, string $objectClass, ?string $classFile): ?array
    {
        if (isset($descriptorData[self::NAME]) && $descriptorData[self::NAME] === false) {
            return ['to' => false, 'from' => false];
        }
        $value = ['to' => true, 'from' => true];
        if (isset($descriptorData[self::NAME]) && $descriptorData[self::NAME] === true) {
            return $value;
        }

        if (isset($descriptorData[self::NAME]) && is_string($descriptorData[self::NAME])) {
            $value = ['to' => $descriptorData[self::NAME], 'from' => $descriptorData[self::NAME]];
        }
        if (isset($descriptorData[self::NAME]) && is_array($descriptorData[self::NAME])) {
            $value = array_merge($value, $descriptorData[self::NAME]);
        }

        if (isset($descriptorData[self::NAME . 'To'])) {
            $value['to'] = $descriptorData[self::NAME . 'To'];
        }
        if (isset($descriptorData[self::NAME . 'From'])) {
            $value['from'] = $descriptorData[self::NAME . 'From'];
        }
        return $value;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    private function applyCasing(string $caseName, $value)
    {
        if (!is_string($value)) {
            return $value;
        }
        $case = $this->options[$caseName] ?: $this->options['case'];
        switch ($case) {
            case 'snake':
                $value = Str::snake($value);
                break;
            case 'camel':
                $value = Str::camel($value);
                break;
            case 'studly':
                $value = Str::studly($value);
                break;
        }
        return $value;
    }
}