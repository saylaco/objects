<?php

namespace Sayla\Objects\Contract;

interface Triggerable
{

    public function __invoke(string $name, ...$arguments);
    
    public function getTriggerCount(string $triggerName): int;

    public function hasTriggerListeners(string $triggerName): bool;
}