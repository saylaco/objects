<?php

namespace Sayla\Objects\Attribute\Resolver;

use Sayla\Objects\Contract\AttributeResolver;
use Sayla\Objects\Contract\AttributeResolverTrait;
use Sayla\Objects\DataObject;

class CallableResolver implements AttributeResolver
{
    use AttributeResolverTrait;
    /** @var callable|\Closure */
    protected $callable;
    /** @var bool */
    protected $isClosure = false;

    public function __construct($callable)
    {
        $this->setCallable($callable);
    }

    /**
     * @param callable $callable
     * @return $this
     */
    public function setCallable($callable)
    {
        $this->callable = $callable;
        $this->isClosure = $callable instanceof \Closure;
        return $this;
    }

    /**
     * @param \Sayla\Objects\DataObject $owningObject
     * @return mixed
     */
    public function resolve(DataObject $owningObject)
    {
        if ($this->isClosure) {
            $closure = $this->callable->bindTo($owningObject);
            return $closure($owningObject);
        }
        return call_user_func($this->callable, $owningObject);
    }

    public function resolveMany($objects): array
    {
        return $this->resolveManyUsingSingleResolver($objects);
    }
}