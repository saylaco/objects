<?php

namespace Sayla\Objects\Contract;

interface Triggerable
{

    public function __invoke(string $name, ...$arguments);

    /**
     * @param string $triggerKey
     * @return int
     */
    public function getTriggerCount(string $triggerKey): int;
}