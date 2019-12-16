<?php

namespace Sayla\Objects\Attribute\Resolver;

use Sayla\Objects\Contract\Attributes\AttributeResolver;
use Sayla\Objects\Contract\Attributes\AttributeResolverTrait;
use Sayla\Objects\Contract\Attributes\SupportsCallableResolverTrait;
use Sayla\Objects\Contract\IDataObject;

class EnumResolver implements AttributeResolver
{
    use SupportsCallableResolverTrait;
    use AttributeResolverTrait;
    /**
     * @var \BenSampo\Enum\Enum
     */
    public $enumClass;

    /**
     * EnumResolver constructor.
     * @param \BenSampo\Enum\Enum $enumClass
     */
    public function __construct($enumClass, $valueAttributeName = null)
    {
        $this->enumClass = $enumClass;
        $this->setOwnerAttributeName($valueAttributeName ?? lcfirst(class_basename($enumClass)) . 'Name');
    }


    public function resolve(IDataObject $object)
    {
        $value = $object[$this->getAttribute()];
        if ($value === null) {
            return null;
        }
        $enumClass = $this->enumClass;
        return $enumClass::getInstance($value);
    }

    public function resolveMany($objects): array
    {
        return $this->resolveManyUsingSingleResolver($objects);
    }

}