<?php

namespace Sayla\Objects\Pipes;

class ModelPipeManager extends ObjectPipeManager
{

    public function onObjectCreate($object, $laravelCallable, $key = null)
    {
        $objectClass = is_object($object) ? get_class($object) : $object;
        $this->addPipe('create', $objectClass, $laravelCallable, $key);
        return $this;
    }

    public function onObjectDelete($object, $laravelCallable, $key = null)
    {
        $objectClass = is_object($object) ? get_class($object) : $object;
        $this->addPipe('delete', $objectClass, $laravelCallable, $key);
        return $this;
    }

    public function onObjectUpdate($object, $laravelCallable, $key = null)
    {
        $objectClass = is_object($object) ? get_class($object) : $object;
        $this->addPipe('update', $objectClass, $laravelCallable, $key);
        return $this;
    }
}