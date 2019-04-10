<?php

namespace Sayla\Objects\Contract;

interface NormalizesPropertyValue
{
    public function normalizePropertyValue(array $descriptorData, string $objectClass, ?string $classFile): ?array;
}