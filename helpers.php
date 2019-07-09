<?php

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Sayla\Objects\AttributeResolverManager;
use Sayla\Objects\Stubs\StubFactory;
use Sayla\Objects\Transformers\TransformerFactory;
use Sayla\Util\JsonHelper;

if (!function_exists('stub')) {
    /**
     * Create a object factory builder for a given class, name, and amount.
     *
     * @param mixed[] $arguments class|class,name|class,amount|class,name,amount
     * @return \Sayla\Objects\Stubs\StubBuilder
     */
    function stub($class, ...$arguments)
    {
        /** @var \Sayla\Objects\Stubs\StubFactory $factory */
        $factory = StubFactory::resolve()->make(StubFactory::class);
        if (isset($arguments[0]) && is_string($arguments[0])) {
            return $factory->of($class, $arguments[0])->times(isset($arguments[1]) ? $arguments[1] : null);
        } elseif (isset($arguments[0])) {
            return $factory->of($class)->times($arguments[0]);
        } else {
            return $factory->of($class);
        }
    }
}
if (!function_exists('attribute_resolver')) {
    function attribute_resolver()
    {
        throw new BadFunctionCallException('deprecated');
    }
}
if (!function_exists('build_value')) {
    /**
     * @param string $transformer
     * @param mixed $value
     * @param \Sayla\Objects\Transformers\Options|array|null $options
     * @return mixed
     */
    function build_value(string $transformer, $value = null, $options = null)
    {
        return TransformerFactory::resolve()
            ->getTransformer($transformer, $options)
            ->build($value);
    }
}

if (!function_exists('smash_value')) {
    /**
     * @param string $transformer
     * @param mixed $value
     * @param \Sayla\Objects\Transformers\Options|array|null $options
     * @return mixed
     */
    function smash_value(string $transformer, $value, $options = null)
    {
        return TransformerFactory::resolve()
            ->getTransformer($transformer, $options)
            ->smash($value);
    }
}