<?php

namespace Sayla\Objects\Contract\DataObject;

interface ProvidesRules
{

    public static function getValidationRules(): array;
}