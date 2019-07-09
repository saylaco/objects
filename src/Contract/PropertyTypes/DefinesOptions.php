<?php

namespace Sayla\Objects\Contract\PropertyTypes;

interface DefinesOptions
{
    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     * @return mixed
     */
    public static function defineOptions($resolver);

    public function setOptions(array $options);
}