<?php

namespace Sayla\Objects\Attribute\Property;

use Illuminate\Support\Str;
use Sayla\Objects\Contract\PropertyType;

class ValidationPropertyType implements PropertyType
{

    public static function getHandle(): string
    {
        return 'validation';
    }

    /**
     * @return string[]|void
     */
    public function getDefinitionKeys(): ?array
    {
        return ['rules', 'deleteRules', 'updateRules', 'createRules', 'validationLabel'];
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

    /**
     * @param $attributeName
     * @return string
     */
    protected function toTitle($attributeName): string
    {
        return Str::title(str_replace(['-', '_'], ' ', $attributeName));
    }
}