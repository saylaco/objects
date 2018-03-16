<?php

namespace Sayla\Objects\Pipes;

use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;

class ObjectPipeManager implements PipeManager
{
    protected $container;
    private $pipes = [];
    private $delegates = [];

    public function __construct(Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param string $group
     * @param string|null $objectClass
     * @param             $laravelCallable
     * @param string|null $key
     * @return $this
     */
    public function addPipe(string $group, string $objectClass, $laravelCallable, string $key = null)
    {
        if ($key != null) {
            $this->pipes[$group][$objectClass][$key] = $laravelCallable;
        } else {
            $this->pipes[$group][$objectClass][] = $laravelCallable;
        }
        return $this;
    }

    public function addPipeDelegate(PipeDelegate $delegate)
    {
        $class = get_class($delegate);
        if (isset($this->delegates[$class])) {
            return;
        }
        $delegate->registerPipes($this);
        $this->delegates[$class] = true;
    }

    /**
     * @param             $object
     * @param iterable $pipeGroups
     * @param string|null $defaultMethod
     * @return \Illuminate\Pipeline\Pipeline
     */
    public function getBundle($object, iterable $pipeGroups, string $defaultMethod = null)
    {
        $objectClass = method_exists($object, 'getPipelineName') ? $object->getPipelineName() : get_class($object);
        return $this->makeBundledPipeline($pipeGroups, $objectClass, $defaultMethod)->send($object);
    }

    /**
     * @param Object|string $objectClass
     * @param string $pipeGroup
     * @return int
     */
    public function getPipeCount($objectClass, string $pipeGroup): int
    {
        $objectClass = is_object($objectClass) ? get_class($objectClass) : $objectClass;
        return count($this->getPipes($pipeGroup, $objectClass));
    }

    /**
     * @param object $object
     * @param string $pipeGroup
     * @param \Closure|null $action
     * @param string|null $defaultMethod
     * @return mixed
     */
    public function run($object, string $pipeGroup, \Closure $action = null, string $defaultMethod = null)
    {
        $objectClass = method_exists($object, 'getPipelineName') ? $object->getPipelineName() : get_class($object);
        return $this->makePipeline($pipeGroup, $objectClass, $defaultMethod)
            ->send($object)
            ->then($action);
    }

    /**
     * @param string[]|iterable $pipes
     * @param string $objectClass
     * @param string|null $defaultMethod
     * @return \Illuminate\Pipeline\Pipeline
     */
    protected function makeBundledPipeline(iterable $pipes, string $objectClass,
                                           string $defaultMethod = null): Pipeline
    {
        $pipeline = new Pipeline($this->container);
        $pipeBundle = [];
        foreach ($pipes as $pipe) {
            $pipeBundle = array_merge($pipeBundle, $this->getPipes($pipe, $objectClass));
        }
//        logger()->info(count($pipeBundle) . ' pipes for ' . join(',', $pipes) . '(' . $objectClass . ')');
        $pipeline->through($pipeBundle);
        if ($defaultMethod !== null) {
            $pipeline->via($defaultMethod);
        }
        return $pipeline;
    }

    /**
     * @param string $group
     * @param string $objectClass
     * @return array
     */
    protected function getPipes(string $group, string $objectClass)
    {
        return $this->pipes[$group][$objectClass] ?? [];
    }

    /**
     * @param string $pipeGroup
     * @param string $objectClass
     * @param string|null $defaultMethod
     * @return \Illuminate\Pipeline\Pipeline
     */
    protected function makePipeline(string $pipeGroup, string $objectClass, string $defaultMethod = null): Pipeline
    {
        $pipeline = new Pipeline($this->container);
        $pipes = $this->getPipes($pipeGroup, $objectClass);
//        logger()->info(count($pipes) . ' pipes for ' . $pipeGroup . '(' . $objectClass . ')');
        $pipeline->through($pipes);
        if ($defaultMethod !== null) {
            $pipeline->via($defaultMethod);
        }
        return $pipeline;
    }
}