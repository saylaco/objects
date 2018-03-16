<?php

namespace Sayla\Objects;

trait StorableObjectTrait
{
    public function delete(): bool
    {
        $this->getStore()->delete($this);
        $this->exists = false;
        return true;
    }

    public function save(): bool
    {
        if (!$this->exists()) {
            return $this->create();
        }
        return $this->update();
    }

    public function create(): bool
    {
        if ($this->exists) {
            return false;
        }
        $this->getStore()->create($this);
        $this->exists = true;
        return true;
    }

    public function update(): bool
    {
        $this->getStore()->update($this);
        $this->exists = true;
        return true;
    }
}