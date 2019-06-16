<?php

namespace Sayla\Objects\Contract\Attributes;

use Closure;
use Sayla\Objects\DataObject;

trait SupportsCallableResolverTrait
{
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
        $this->isClosure = $callable instanceof Closure;
        return $this;
    }

    /**
     * @param \Sayla\Objects\DataObject $owningObject
     * @return mixed
     */
    private function runCallable(DataObject $owningObject)
    {
        if ($this->isClosure) {
            $closure = $this->callable->bindTo($owningObject);
            return $closure($owningObject);
        }
        return call_user_func($this->callable, $owningObject);
    }
}