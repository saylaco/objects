<?php

namespace Sayla\Objects\Contract;

interface PropertyType
{

    /**
     * @return string[]|null
     */
    public function getDefinitionKeys(): ?array;

    public function getName(): string;

    /**
     * @param string $attributeName
     * @param $propertyValue
     * @param string $attributeType
     * @param string $objectClass
     * @return \Sayla\Objects\Contract\Property
     */
    public function getPropertyValue(string $attributeName, $propertyValue, string $attributeType, string $objectClass);
}