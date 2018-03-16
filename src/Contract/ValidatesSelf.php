<?php

namespace Sayla\Objects\Contract;

interface ValidatesSelf
{
    public function validateCreation(): void;

    public function validateDeletion(): void;

    public function validateModification(): void;
}