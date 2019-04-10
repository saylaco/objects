<?php

namespace Sayla\Objects\Attribute\PropertyType;

trait SupportAutoAnnotationTrait
{
    /** @var bool[] */
    protected static $alwaysApplyByClass = [];

    public static function applyAutomatically(string $class)
    {
        return self::$alwaysApplyByClass[$class] ?? self::$alwaysApplyByClass['*'] ?? false;
    }

    public static function enableDefaultAnnotation(string $class = '*', bool $enableDefault = true)
    {
        self::$alwaysApplyByClass[$class] = $enableDefault;
    }
}