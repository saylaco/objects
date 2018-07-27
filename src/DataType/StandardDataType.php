<?php

namespace Sayla\Objects\DataType;

use Sayla\Objects\Attribute\DefaultPropertyTypeSet;
use Sayla\Objects\Attribute\PropertyTypeSet;
use Sayla\Objects\Contract\DataType;
use Sayla\Objects\ObjectDispatcher;
use Sayla\Objects\SimpleEventDispatcher;
use Sayla\Objects\Transformers\ValueTransformerFactory;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StandardDataType extends BaseDataType
{

    public static function build(array $options)
    {
        $dataType = new static;
        static::mapOptions($dataType, $options);
        return $dataType;
    }

    public static function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['objectClass', 'attributeDefinitions']);

        $resolver->setDefault('attributeDefinitions', function (Options $options) {
            return forward_static_call($options['objectClass'] . '::getDefinedAttributes');
        });

        $resolver->setAllowedTypes('objectClass', 'string');
        $resolver->setAllowedTypes('attributeDefinitions', 'array');

        $resolver->setDefault('valueFactory', ValueTransformerFactory::getInstance());
        $resolver->setAllowedTypes('valueFactory', ValueTransformerFactory::class);

        $resolver->setDefault('objectDispatcher', function (Options $options) {
            return new ObjectDispatcher(new SimpleEventDispatcher(), $options['name']);
        });
        $resolver->setAllowedTypes('objectDispatcher', ObjectDispatcher::class);

        $resolver->setDefault('name', function (Options $options) {
            return $options['objectClass'];
        });
        $resolver->setAllowedTypes('name', 'string');

        $resolver->setDefault('objectDispatcher', function (Options $options) {
            return new ObjectDispatcher(new SimpleEventDispatcher(), $options['name']);
        });

        $resolver->setDefault('propertyTypes', function (Options $options) {
            return new DefaultPropertyTypeSet();
        });
        $resolver->setAllowedTypes('propertyTypes', PropertyTypeSet::class);
    }

    /**
     * @param array $options
     * @param static $dataType
     */
    public static function mapOptions(DataType $dataType, array $options)
    {
        $dataType->dispatcher = $options['objectDispatcher'];
        $dataType->valueFactory = $options['valueFactory'];
        $dataType->propertyTypes = $options['propertyTypes'];
        $dataType->objectClass = $options['objectClass'];
        $dataType->attributeDefinitions = $options['attributeDefinitions'];
        $dataType->name = $options['name'];
    }
}