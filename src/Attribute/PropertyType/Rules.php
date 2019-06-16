<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Illuminate\Support\Str;
use Sayla\Objects\Contract\DataObject\ProvidesRules;
use Sayla\Objects\Contract\DataObject\StorableObject;
use Sayla\Objects\Contract\PropertyTypes\AttributePropertyType;
use Sayla\Objects\Contract\PropertyTypes\NormalizesPropertyValue;
use Sayla\Util\Mixin\Mixin;

/**
 * Class Rules
 * @see https://laravel.com/docs/5.8/validation#using-extensions for rule parameters
 */
class Rules implements AttributePropertyType, NormalizesPropertyValue
{
    const DEFAULT_VALUE = [
        self::NAME => [
            self::SHARED_RULES => [],
            'save' => [],
            'create' => [],
            'delete' => [],
            'update' => [],
        ],
        'label' => null,
        'errMsg' => null
    ];
    const IDENTITY_PROPERTIES = [self::NAME, 'label', 'errMsg'];
    const NAME = 'rules';
    const SHARED_RULES = 'all';
    private static $allValidators = [];
    private $classValidators = [];
    private $validators = [];

    public static function getProviders(): array
    {
        return [
            self::PROVIDER_DESCRIPTOR_MIXIN => function (string $dataType, array $properties): Mixin {
                return new RulesDescriptorMixin($dataType, $properties);
            },
            self::ON_BEFORE_CREATE => function (StorableObject $object) {
                /** @var RulesDescriptorMixin $descriptor */
                $descriptor = $object::descriptor();
                $descriptor->validateCreate($object);
            },
            self::ON_BEFORE_UPDATE => function (StorableObject $object) {
                /** @var RulesDescriptorMixin $descriptor */
                $descriptor = $object::descriptor();
                $descriptor->validateUpdate($object);
            },
            self::ON_BEFORE_DELETE => function (StorableObject $object) {
                /** @var RulesDescriptorMixin $descriptor */
                $descriptor = $object::descriptor();
                $descriptor->validateDelete($object);
            }
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPropertyValue(string $attributeName, array $value, string $attributeType): ?array
    {
        if (empty($value['label'])) {
            $value['label'] = $this->toTitle($attributeName);
        }

        foreach ($value['rules'] as $ruleSet => $rules)
            $value['rules'][$ruleSet] = $this->normalizeRules($rules);

        $value['validators'] = $this->validators;
        return $value;
    }

    /**
     * @param array $descriptorData
     * @param string|ProvidesRules $objectClass
     * @param string|null $classFile
     * @return array|null
     */
    public function normalizePropertyValue(array $descriptorData, string $objectClass, ?string $classFile): ?array
    {
        if (!isset(self::$allValidators[$objectClass])) {
            $extensions = [];
            if (is_subclass_of($objectClass, ProvidesRules::class)) {
                $prefix = class_root_namespace($objectClass) . ' ' . class_basename($objectClass);
                foreach ($objectClass::getValidationRules() as $key => $handlers) {
                    $extensions[] = [
                        'alias' => "@{$key}",
                        'key' => $key,
                        'name' => snake_case("{$prefix} {$key}"),
                        'callback' => "{$objectClass}::getValidationRules",
                        'handler' => true,
                        'replacer' => is_array($handlers),
                    ];
                }
            }
//            $instanceRules = preg_grep(
//                RulesDescriptorMixin::VALIDATION_RULES_METHOD_PATTERN,
//                get_class_methods($objectClass)
//            );
            self::$allValidators[$objectClass] = compact('extensions');
        }

        $this->classValidators = array_replace_key(self::$allValidators[$objectClass]['extensions'], 'alias');

        $overrides = [];
        if (is_string($descriptorData['rules'])) {
            $overrides['rules'][self::SHARED_RULES] = $this->normalizeRules($descriptorData['rules']);
            $descriptorData['rules'] = [];
        }
        return array_merge_recursive(
            self::DEFAULT_VALUE,
            array_only($descriptorData, self::IDENTITY_PROPERTIES),
            $overrides
        );
    }

    /**
     * @param $rules
     * @return array
     */
    protected function normalizeRules($rules): array
    {
        if (!is_array($rules)) {
            $rules = [$rules];
        }
        $normalized = [];
        foreach ($rules as $i => $rule) {
            $normalized += explode('|', $rule);
        }
        $normalized = array_unique($normalized);
        foreach ($normalized as $i => $rule)
            if (isset($this->classValidators[$rule]['name'])) {
                $this->validators[] = $this->classValidators[$rule];
                $normalized[$i] = $this->classValidators[$rule]['name'];
            }
        return $normalized;
    }

    /**
     * @param $attributeName
     * @return string
     */
    protected function toTitle($attributeName): string
    {
        return Str::title(str_replace(['-', '_'], ' ', $attributeName));
    }
}