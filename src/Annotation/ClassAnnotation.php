<?php

namespace Sayla\Objects\Annotation;

use ReflectionClass;

interface ClassAnnotation
{
    public function process(ReflectionClass $class);
}