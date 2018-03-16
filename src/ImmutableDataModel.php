<?php

namespace Sayla\Objects;

abstract class ImmutableDataModel extends DataModel
{
    use ImmutableDataObjectTrait;

    /**
     * @return static
     */
    public function create()
    {
        $object = parent::create();
        $this->resetDirty();
        return $object;
    }

    public function delete()
    {
        $object = parent::delete();
        $this->resetDirty();
        return $object;
    }

    protected function getStoreInstance()
    {
        return $this->getDirtyObject();
    }

    public function update()
    {
        $object = parent::update();
        $this->resetDirty();
        $this->setAttributes($object->toArray());
        return $object;
    }

    /**
     * @return array
     */
    public function getDirty()
    {
        return array_diff_assoc($this->workingAttributes, $this->toArray());
    }

    public function isDirty($key = null)
    {
        if ($key === null) {
            return !empty($this->workingAttributes);
        }
        return ($this->workingAttributes[$key] ?? null) != $this->getRawAttribute($key);
    }
}