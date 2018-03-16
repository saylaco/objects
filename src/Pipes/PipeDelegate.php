<?php

namespace Sayla\Objects\Pipes;
/**
 * Interface PipeDelegate
 */
interface PipeDelegate
{
    /**
     * @param ObjectPipeManager $manager
     */
    public function registerPipes($manager): void;
}