<?php

namespace Sayla\Objects\DataType;

use Sayla\Objects\Contract\DataType;
use Sayla\Objects\Contract\PersistentDataType;
use Sayla\Objects\Contract\PersistentDataTypeTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoringDataType extends BaseDataType implements PersistentDataType
{
    use PersistentDataTypeTrait;

    public static function build(array $options)
    {
        $dataType = new static;
        static::mapOptions($dataType, $options);
        return $dataType;
    }

    public static function configureOptions(OptionsResolver $resolver)
    {
        StandardDataType::configureOptions($resolver);
        $resolver->setRequired('storeName');
        $resolver->setAllowedTypes('storeName', 'string');
    }

    /**
     * @param array $options
     * @param static $dataType
     */
    public static function mapOptions(DataType $dataType, array $options)
    {
        StandardDataType::mapOptions($dataType, $options);
        $dataType->storeName = $options['storeName'];
    }
}