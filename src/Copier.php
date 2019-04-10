<?php

namespace Sayla\Objects;

use DeepCopy\DeepCopy;
use DeepCopy\TypeFilter\ReplaceFilter;
use DeepCopy\TypeMatcher\TypeMatcher;
use Sayla\Helper\Data\SimpleObject;
use Sayla\Objects\Contract\Attributable;

class Copier
{
    /** @var \DeepCopy\DeepCopy */
    private $copier;

    /**
     * DataObjectCopier constructor.
     * @param $copier
     */
    public function __construct(DeepCopy $copier = null)
    {
        $this->copier = $copier ?? $this->makeCopier();
    }

    public function copyAttributes(Attributable $object): array
    {
        $simpleObject = $this->copier->copy(new SimpleObject($object->toArray()));
        return (array)$simpleObject;
    }

    public function copyObject(Attributable $object): Attributable
    {
        $className = get_class($object);
        $data = $this->copyAttributes($object);
        return new $className($data);
    }

    /**
     * @return \DeepCopy\DeepCopy
     */
    protected function makeCopier(): DeepCopy
    {
        $copier = new DeepCopy();
        $copier->skipUncloneable(true);
        $copier->addTypeFilter(new ReplaceFilter(function ($value) {
            return $this->copyObject($value);
        }), new TypeMatcher(Attributable::class));
        return $copier;
    }
}