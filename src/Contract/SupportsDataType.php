<?php

namespace Sayla\Objects\Contract;

interface SupportsDataType
{
    public function getDataType(): string;

    public function setDataType(string $descriptor);
}