<?php

namespace Sayla\Objects\Attribute\PropertyType;

use Sayla\Objects\Contract\DataObject\StorableObject;
use Sayla\Objects\Contract\PropertyTypes\AttributePropertyType;
use Sayla\Util\Mixin\Mixin;

class Owned implements AttributePropertyType
{
    const NAME = 'owned';

    public static function getProviders(): array
    {
        return [
            self::PROVIDER_DESCRIPTOR_MIXIN => function (string $dataType, array $properties): Mixin {
                return new OwnedDescriptorMixin($dataType, $properties);
            },
            self::ON_BEFORE_CREATE => function (StorableObject $object) {
                /** @var OwnedDescriptorMixin $descriptor */
                $descriptor = $object::descriptor();
                $descriptor->assignOwner($object);
            },
            self::ON_BEFORE_UPDATE => function (StorableObject $object) {
                /** @var OwnedDescriptorMixin $descriptor */
                $descriptor = $object::descriptor();
                $descriptor->assignOwnerIf($object);
            }
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPropertyValue(string $attributeName, array $value, string $attributeType): ?array
    {
        if (empty($value['value']) || $value['value'] === true) {
            $value['value'] = 'id';
        }
        return $value;
    }

}