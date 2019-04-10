<?php

namespace Sayla\Objects\Contract;
/**
 * @mixin \Sayla\Objects\Contract\Serializes
 */
trait SerializesTrait
{
    public function __sleep(): array
    {
        $properties = get_object_vars($this);
        foreach (static::unserializableInstanceProperties() as $property) {
            if (is_string($property)) {
                unset($properties[$property]);
            } elseif (is_callable($property)) {
                $propertiesToIgnore = (array)$property($properties);
                array_forget($properties, $propertiesToIgnore);
            }
        }
        return array_keys($properties);
    }
}