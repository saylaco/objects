<?php

namespace Sayla\Objects\Attribute\Mixin;

use Sayla\Helper\Data\Contract\ProvidesArrayAccessTrait;
use Sayla\Objects\Contract\Mixin;
use Sayla\Objects\Set;

/**
 * @method getIterator() Mixin[]
 */
class MixinSet extends Set
{
    use ProvidesArrayAccessTrait;
    private $callableMethods = [];

    public function call(string $methodName, array $arguments)
    {
        if (str_contains($methodName, '_')) {
            [$mixinName, $methodName] = str_split($methodName, '_');
        } elseif (isset($this->callableMethods[$methodName])) {
            $mixinName = $this->callableMethods[$methodName];
        } else {
            throw new \BadMethodCallException('Mixin not found - ' . $methodName);
        }
        return call_user_func_array([$this[$mixinName], $methodName], $arguments);
    }

    public function put(string $name, Mixin $item)
    {
        $this->items[$name] = $item;
        $methods = get_class_methods($item);
        foreach ($methods as $methodName) {
            if (starts_with($methodName, '__')) continue;
            $this->callableMethods[$methodName] = $name;
        }
    }
}