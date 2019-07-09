<?php

namespace Sayla\Objects\Contract\Stores;

interface ConfigurableStore
{
    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public static function defineOptions($resolver): void;

    public function setOptions(string $name, array $options): void;
}