<?php

namespace Sayla\Objects\Contract\PropertyTypes;

interface ModifiesAttributeDescriptor
{
    public function modifyDescriptor(array $propertyValue, array $normalizedProperties): ?array;
}