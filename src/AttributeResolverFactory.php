<?php

namespace Sayla\Objects;

use Illuminate\Support\Traits\Macroable;
use ReflectionClass;
use ReflectionMethod;
use Sayla\Objects\Resolvers\AliasResolver;
use Sayla\Objects\Resolvers\AttributeResolver;
use Sayla\Objects\Resolvers\CallableResolver;

class AttributeResolverFactory
{
    use Macroable {
        __call as protected callMacro;
        mixin as private _mixin;
    }

    /**
     * @param $method
     * @param $parameters
     * @return \Sayla\Objects\Resolvers\AttributeResolver
     */
    public function __call($method, $parameters): AttributeResolver
    {
        return $this->callMacro($method, $parameters);
    }

    public function alias(string $expression)
    {
        return new AliasResolver($expression);
    }

    public function callable($callable)
    {
        return new CallableResolver($callable);
    }

    /**
     * Mix in methods matching "^resolve(\w+)Type$"
     *
     * @param  object $mixin
     * @return void
     */
    public function mixin($mixin)
    {
        $methods = (new ReflectionClass($mixin))->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if (starts_with($method->name, 'resolve') && ends_with($method->name, 'Type')) {
                static::macro(lcfirst(substr($method->name, 7, -4)), [$mixin, $method->name]);
            }
        }
    }

}