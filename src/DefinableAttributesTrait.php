<?php

namespace Sayla\Objects;


use Sayla\Objects\Inspection\ObjectDescriptor;
use Sayla\Objects\Inspection\ObjectDescriptors;

trait DefinableAttributesTrait
{
    private static $descriptors;
    protected $descriptor;

    public static function getDefinedAttributes(): array
    {
        return [];
    }

    final public function descriptor(): ObjectDescriptor
    {
        return static::getDescriptor($this->descriptor);
    }

    public static function getDescriptor($descriptorName = null): ObjectDescriptor
    {
        $name = $descriptorName ?? static::class;
        return self::getDescriptors()->getDescriptor($name);
    }

    /**
     * @return \Sayla\Objects\Inspection\ObjectDescriptors
     */
    public static function getDescriptors(): ObjectDescriptors
    {
        if (!isset(self::$descriptors)) {
            self::$descriptors = new ObjectDescriptors();
        }
        return self::$descriptors;
    }

    /**
     * @param \Sayla\Objects\Inspection\ObjectDescriptors $descriptors
     */
    public static function setDescriptors(ObjectDescriptors $descriptors)
    {
        self::$descriptors = $descriptors;
    }

    /**
     * @param string $descriptor
     */
    public function setDescriptor(string $descriptor)
    {
        $this->descriptor = $descriptor;
    }
}