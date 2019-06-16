<?php

namespace Sayla\Objects\Attribute\Resolver;

use Sayla\Objects\Contract\Attributes\AttributeResolver;
use Sayla\Objects\Contract\Attributes\AttributeResolverTrait;
use Sayla\Objects\Contract\Attributes\SupportsCallableResolverTrait;
use Sayla\Objects\DataObject;

class CallableResolver implements AttributeResolver
{
    use SupportsCallableResolverTrait;
    use AttributeResolverTrait;

    public function __construct($callable)
    {
        $this->setCallable($callable);
    }

    /**
     * @param \Sayla\Objects\DataObject $owningObject
     * @return mixed
     */
    public function resolve(DataObject $owningObject)
    {
        return $this->runCallable($owningObject);
    }

    public function resolveMany($objects): array
    {
        return $this->resolveManyUsingSingleResolver($objects);
    }

}