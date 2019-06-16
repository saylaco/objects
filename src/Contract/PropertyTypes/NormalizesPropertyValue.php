<?php

namespace Sayla\Objects\Contract\PropertyTypes;

interface NormalizesPropertyValue
{
    public function normalizePropertyValue(array $descriptorData, string $objectClass, ?string $classFile): ?array;
}