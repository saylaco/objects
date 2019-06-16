<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Contract\IDataObject;
use Sayla\Util\Mixin\Mixin;

class OwnedDescriptorMixin implements Mixin
{
    /** @var callable */
    private static $defaultUserAttributeCallback;
    /**
     * @var string
     */
    private $dataType;
    /**
     * @var \Illuminate\Support\Collection|\Sayla\Objects\Attribute\Property[]
     */
    private $properties;
    /** @var callable */
    private $userAttributeCallback;

    public function __construct(string $dataType, array $properties)
    {
        $this->properties = collect($properties)->filter();
        $this->dataType = $dataType;
    }

    public function assignOwner(IDataObject $object)
    {
        foreach ($this->properties as $attributeName => $property)
            $object[$attributeName] = $this->getUserAttribute($property['value']);
    }

    public function assignOwnerIf(IDataObject $object)
    {
        foreach ($this->properties as $attributeName => $property) {
            if ($object->isAttributeFilled($attributeName)) continue;
            $object[$attributeName] = $this->getUserAttribute($property['value']);
        }
    }

    public function getUserAttribute($attribute)
    {
        return call_user_func($this->getUserAttributeCallback(), $attribute);
    }

    /**
     * @return callable
     */
    public function getUserAttributeCallback(): callable
    {
        return $this->userAttributeCallback ?? self::$defaultUserAttributeCallback;
    }

    /**
     * @param callable $defaultUserAttributeCallback
     */
    public static function setDefaultUserAttributeCallback(callable $defaultUserAttributeCallback): void
    {
        self::$defaultUserAttributeCallback = $defaultUserAttributeCallback;
    }

}