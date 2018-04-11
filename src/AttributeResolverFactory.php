<?php

namespace Sayla\Objects;

use Illuminate\Support\Traits\Macroable;
use ReflectionClass;
use ReflectionMethod;
use Sayla\Objects\Contract\AttributeResolver;
use Sayla\Objects\Resolvers\AliasResolver;
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
     * @return AttributeResolver
     */
    public function __call($method, $parameters): AttributeResolver
    {
        return $this->callMacro($method, $parameters);
    }

    public function alias(string $expression, string $dependsOn = null)
    {
        return new AliasResolver($expression, $dependsOn);
    }

    public function callable($callable)
    {
        return new CallableResolver($callable);
    }

    /**
     * Mix in methods matching "^get(\w+)AttributeResolver$"
     *
     * @param  object $mixin
     * @return void
     */
    public function mixin($mixin)
    {
        $methods = (new ReflectionClass($mixin))->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if (starts_with($method->name, 'get') && ends_with($method->name, 'AttributeResolver')) {
                $macroName = lcfirst(str_before(substr($method->name, 3), 'AttributeResolver'));
                static::macro($macroName, [$mixin, $method->name]);
            }
        }
    }

}