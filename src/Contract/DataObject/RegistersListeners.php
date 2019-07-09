<?php

namespace Sayla\Objects\Contract\DataObject;
/**
 * Interface RegistersListeners
 * @scannedMethods register*Listener(\Sayla\Objects\ObjectDispatcher $dispatcher)
 */
interface RegistersListeners
{
    /**
     * @param \Sayla\Objects\ObjectDispatcher $dispatcher
     * @return mixed
     */
    public static function registerListeners($dispatcher);
}