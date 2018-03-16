<?php

namespace Sayla\Objects\Pipes;

class NullPipeManager implements PipeManager
{

    /**
     * {@inheritdoc}
     */
    public function addPipe(string $group, string $objectClass, $laravelCallable, string $key = null)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addPipeDelegate(PipeDelegate $delegate)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getBundle($object, iterable $pipeGroups, string $defaultMethod = null)
    {
        return new \Illuminate\Pipeline\Pipeline();
    }


    /**
     * {@inheritdoc}
     */
    public function getPipeCount($objectClass, string $pipeGroup): int
    {
        return 0;
    }


    /**
     * {@inheritdoc}
     */
    public function run($object, string $pipeGroup, \Closure $action)
    {
        return call_user_func($action, $object);
    }
}