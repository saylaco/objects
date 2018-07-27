<?php

namespace Sayla\Objects\Contract;

interface Storable extends Keyable
{
    public function create();

    public function delete();

    public function exists();

    public function update();
}