<?php

namespace Sayla\Objects\Contract;

interface Serializes
{
    /**
     * @return iterable|string[]|callable[]
     */
    public static function unserializableInstanceProperties(): iterable;

    public function __sleep(): array;
}