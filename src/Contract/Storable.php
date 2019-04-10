<?php

namespace Sayla\Objects\Contract;

interface Storable extends Keyable
{
    /**
     * @return ObjectStore
     */
    public static function getStore();

    public function create();

    public function delete();

    public function exists();

    public function update();
}