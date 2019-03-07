<?php

namespace Sayla\Objects\Contract;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface ConfigurableStore
{
    public static function defineOptions(OptionsResolver $resolver): void;

    public function setOptions(string $name, array $options): void;
}