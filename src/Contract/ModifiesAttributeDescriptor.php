<?php

namespace Sayla\Objects\Contract;

interface ModifiesAttributeDescriptor extends NormalizesPropertyValue
{
    public function modifyDescriptor(array $propertyValue, array $normalizedProperties): ?array;
}