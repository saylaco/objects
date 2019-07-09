<?php

namespace Sayla\Objects\Attribute\Resolver;

use BenSampo\Enum\Enum;
use Sayla\Objects\Contract\Attributes\AttributeResolver;
use Sayla\Objects\Contract\Attributes\AttributeResolverTrait;
use Sayla\Objects\Contract\Attributes\SupportsCallableResolverTrait;
use Sayla\Objects\Contract\IDataObject;

class EnumResolver implements AttributeResolver
{
    use SupportsCallableResolverTrait;
    use AttributeResolverTrait;

    /**
     * EnumResolver constructor.
     * @param \BenSampo\Enum\Enum $enumClass
     */
    public function __construct($enumClass)
    {
        $this->setCallable(function (IDataObject $object) use ($enumClass): ?Enum {
            $value = $object[$this->getAttribute()];
            return $value === null ? null : $enumClass::getInstance($value);
        });
    }


    public function resolve(IDataObject $owningObject)
    {
        return $this->runCallable($owningObject);
    }

    public function resolveMany($objects): array
    {
        return $this->resolveManyUsingSingleResolver($objects);
    }

}