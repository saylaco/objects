<?php

namespace Sayla\Objects\Pipes;

interface PipeManager
{
    /**
     * @param string $group
     * @param string|null $objectClass
     * @param             $callable
     * @return $this
     */
    public function addPipe(string $group, string $objectClass, $callable);

    public function addPipeDelegate(PipeDelegate $delegate);

    /**
     * @param          $object
     * @param iterable $pipeGroups
     * @return \Illuminate\Pipeline\Pipeline
     */
    public function getBundle($object, iterable $pipeGroups);

    /**
     * @param Object|string $objectClass
     * @param string $pipeGroup
     * @return int
     */
    public function getPipeCount($objectClass, string $pipeGroup): int;

    /**
     * @param object $object
     * @param string $pipeGroup
     * @param \Closure|null $action
     * @return mixed
     */
    public function run($object, string $pipeGroup, \Closure $action);
}