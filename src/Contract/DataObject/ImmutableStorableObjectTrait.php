<?php

namespace Sayla\Objects\Contract\DataObject;

/**
 * @mixin \Sayla\Objects\Contract\DataObject\StorableObjectTrait
 */
trait ImmutableStorableObjectTrait
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

    /**
     * @return array
     */
    public function getDirty()
    {
        return array_diff_assoc($this->workingAttributes, $this->toArray());
    }

    protected function getStoreInstance()
    {
        return $this->getDirtyObject();
    }

    public function isDirty($key = null)
    {
        if ($key === null) {
            return !empty($this->workingAttributes);
        }
        return ($this->workingAttributes[$key] ?? null) != $this->getRawAttribute($key);
    }

    public function update()
    {
        $object = parent::update();
//        $this->setAttributes($object->toArray());
        $this->resetDirty();
        return $object;
    }
}