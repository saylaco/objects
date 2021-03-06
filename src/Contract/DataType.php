<?php

namespace Sayla\Objects\Contract;

use Sayla\Objects\Attribute\Attribute;
use Sayla\Objects\Attribute\AttributeFactory;
use Sayla\Objects\Attribute\PropertyTypeSet;
use Sayla\Objects\DataObject;
use Sayla\Objects\DataType\DataTypeDescriptor;
use Sayla\Objects\ObjectDispatcher;
use Symfony\Component\OptionsResolver\OptionsResolver;

interface DataType
{
    public static function build(array $options);

    public static function configureOptions(OptionsResolver $resolver);

    /**
     * @param $object
     * @return array
     * @throws \Sayla\Objects\Exception\TransformationError
     */
    public function extract($object): array;

    /**
     * @param $object
     * @return array
     */
    public function extractData($object): array;

    /**
     * @param string $attribute
     * @return Attribute
     */
    public function getAttributeDescriptor(string $attribute): Attribute;

    /**
     * @return AttributeFactory
     */
    public function getAttributeDescriptors(): AttributeFactory;

    /**
     * @return string[]|array
     */
    public function getAttributeNames(): array;

    /**
     * @param string $propertyType
     * @return \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[]
     */
    public function getDefinedProperties(string $propertyType);

    public function getDescriptor(): DataTypeDescriptor;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getObjectClass(): string;

    public function getObjectDispatcher(): ObjectDispatcher;

    /**
     * @param string $propertyType
     * @return \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[]
     */
    public function getProperties(string $propertyType);

    /**
     * @return array|\Sayla\Objects\Attribute\DefaultPropertyTypeSet
     */
    public function getPropertySet(): PropertyTypeSet;

    /**
     * @return \Sayla\Objects\Transformers\Transformer
     */
    public function getTransformer(): \Sayla\Objects\Transformers\Transformer;

    /**
     * Hydrate $object with the provided $data.
     *
     * @param iterable $data
     * @return \Sayla\Objects\DataObject
     * @throws \Sayla\Objects\Exception\HydrationError
     */
    public function hydrate($data);

    /**
     * @param  $data
     * @return array|mixed
     * @throws \Sayla\Exception\Error
     */
    public function hydrateData($data);

    /**
     * @param string $class
     * @param iterable $results
     * @return \Sayla\Objects\ObjectCollection|static[]
     * @throws \Sayla\Objects\Exception\HydrationError
     */
    public function hydrateMany(iterable $results);

    /**
     * Hydrate $object with the provided $data.
     *
     * @param iterable $data
     * @return \Sayla\Objects\DataObject
     * @throws \Sayla\Objects\Exception\HydrationError
     */
    public function hydrateObject(DataObject $object, $data);

    /**
     * @return \Sayla\Objects\ObjectCollection
     */
    public function newCollection();
}