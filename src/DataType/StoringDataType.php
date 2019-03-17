<?php

namespace Sayla\Objects\DataType;

use Sayla\Objects\Contract\DataType;
use Sayla\Objects\Contract\PersistentDataType;
use Sayla\Objects\Contract\PersistentDataTypeTrait;
use Sayla\Objects\ObjectCollection;
use Sayla\Objects\Stores\StoreManager;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoringDataType extends BaseDataType implements PersistentDataType
{
    protected $storeName;

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


    /**
     * @return \Sayla\Objects\Contract\ObjectStore
     */
    public function getStoreStrategy(): \Sayla\Objects\Contract\ObjectStore
    {
        return StoreManager::getInstance()->get($this->storeName);
    }
}