<?php

namespace Sayla\Objects\Attribute\Property;

use Illuminate\Support\Str;
use Sayla\Objects\Contract\PropertyType;
use Sayla\Util\Mixin\Mixin;

class ValidationPropertyType implements PropertyType
{

    /**
     * @return string[]|void
     */
    public function getDefinitionKeys(): ?array
    {
        return ['rules', 'deleteRules', 'updateRules', 'createRules', 'validationLabel'];
    }

    public static function getHandle(): string
    {
        return 'validation';
    }

    public function getName(): string
    {
        return self::getHandle();
    }

    public function getPropertyValue(string $attributeName, $propertyValue, string $attributeType, string $objectClass)
    {
        $validationConfig = [];
        $validationConfig['label'] = $propertyValue['label'] ?? $this->toTitle($attributeName);
        $validationConfig['rules']['*'] = $this->normalizeValidationRules($propertyValue['rules']);
        $validationConfig = $propertyValue['messages'] ?? [];
        if (isset($propertyValue['errMsg'])) {
            $validationConfig['messages'][$attributeName] = $propertyValue['errMsg'];
        }
        $validationConfig['rules']['delete'][$attributeName] = $this->normalizeValidationRules($propertyValue['deleteRules']);
        $validationConfig['rules']['update'][$attributeName] = $this->normalizeValidationRules($propertyValue['updateRules']);
        $validationConfig['rules'][$attributeName] = $this->normalizeValidationRules($propertyValue['createRules']);
        return $validationConfig;
    }

    /**
     * @param $attributeName
     * @return string
     */
    protected function toTitle($attributeName): string
    {
        return Str::title(str_replace(['-', '_'], ' ', $attributeName));
    }

    /**
     * @param $rules
     * @return array
     */
    protected function normalizeValidationRules($rules): array
    {
        if (is_string($rules)) {
            return explode('|', $rules);
        }
        return $rules ?? [];
    }
}