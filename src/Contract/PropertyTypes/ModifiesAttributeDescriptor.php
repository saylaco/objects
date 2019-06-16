<?php

namespace Sayla\Objects\Contract\PropertyTypes;

interface ModifiesAttributeDescriptor extends NormalizesPropertyValue
{
    public function modifyDescriptor(array $propertyValue, array $normalizedProperties): ?array;
}