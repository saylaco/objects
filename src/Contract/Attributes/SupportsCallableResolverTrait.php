<?php

namespace Sayla\Objects\Contract\Attributes;

use Closure;
use Illuminate\Support\Str;
use Sayla\Exception\InvalidArgument;
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
        if (!is_callable($callable) &&
            !(is_string($callable) && Str::contains($callable, '@'))
        ) {
            throw new InvalidArgument('Callable must be provided. ' . gettype($callable) . ' received.');
        }
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