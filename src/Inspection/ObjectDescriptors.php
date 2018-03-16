<?php

namespace Sayla\Objects\Inspection;


use Sayla\Objects\DataObject;

class ObjectDescriptors
{

    protected $descriptors = [];

    public function hasDescriptor(string $name): bool
    {
        return isset($this->descriptors[$name]);
    }

    /**
     * @param string $name
     * @param array|\Traversable $attributes
     * @return \Sayla\Objects\DataObject
     */
    public function makeObject(string $name, $attributes = []): DataObject
    {
        return $this->getDescriptor($name)->makeObject($attributes);
    }

    public function getDescriptor($descriptorName): ObjectDescriptor
    {
        if (!isset($this->descriptors[$descriptorName])) {
            $definitions = call_user_func([$descriptorName, 'getDefinedAttributes']);
            $this->addAttributeDefinitions($descriptorName, $definitions, $descriptorName);
        }
        return clone $this->descriptors[$descriptorName];
    }

    /**
     * @param string $name
     * @param array $definitions
     * @throws \Sayla\Exception\Error
     */
    public function addAttributeDefinitions(string $name, array $definitions, string $className = null): void
    {
        $descriptor = new ObjectDescriptor($name, $className);
        $this->getAttributeParser()->parse($descriptor, $definitions);
        $this->descriptors[$name] = $descriptor;
        if (isset($className)) {
            $this->descriptors[$className] = $descriptor;
        }
    }

    public function getAttributeParser(): AttributeDefinitionsParser
    {
        return new AttributeDefinitionsParser();
    }
}