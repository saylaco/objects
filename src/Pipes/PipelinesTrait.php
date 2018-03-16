<?php

namespace Sayla\Objects\Pipes;

trait PipelinesTrait
{
    private static $pipeManager;

    /**
     * @return PipeManager
     */
    public abstract static function getPipes();

    public static function on($event, $listener, $key = null)
    {
        self::getPipes()->addPipe($event, static::getPipelineName(), $listener, $key);
    }

    public static function getPipelineName(): string
    {
        return static::class;
    }

    /**
     * @param PipeManager $pipeManager
     */
    public static function setPipeManager(PipeManager $pipeManager)
    {
        self::$pipeManager = $pipeManager;
    }

}