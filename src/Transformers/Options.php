<?php

namespace Sayla\Objects\Transformers;

use Sayla\Data\ArrayObject;

class Options extends ArrayObject
{
    function __get($name)
    {
        return $this[$name] ?? null;
    }

    function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    function __isset($name)
    {
        return isset($this[$name]);
    }

    function __unset($name)
    {
        unset($this[$name]);
    }

    public function get($name, $default = null)
    {
        return $this[$name] ?? $default;
    }

}