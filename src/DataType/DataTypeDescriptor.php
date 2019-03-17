<?php

namespace Sayla\Objects\DataType;

use Sayla\Objects\ObjectCollection;
use Sayla\Objects\ObjectDispatcher;
use Sayla\Objects\SimpleEventDispatcher;
use Sayla\Util\Mixin\MixinSet;

class DataTypeDescriptor implements \Serializable
{
    /** @var \Sayla\Util\Mixin\MixinSet */
    protected $mixins;
    /** @var string|ObjectCollection $objectCollectionClass */
    protected $objectCollectionClass = ObjectCollection::class;
    /** @var \Illuminate\Support\Collection */
    protected $resolvable = [];
    /** @var string[] */
    private $attributeNames = [];
    /** @var string */
    private $class;
    /** @var string */
    private $dataType;
    /** @var \Illuminate\Contracts\Events\Dispatcher */
    private $eventDispatcher;
    /** @var \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[] */
    private $getFilters = [];
    /** @var \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[] */
    private $setFilters = [];

    /**
     * DataTypeDescriptor constructor.
     * @param \Sayla\Objects\ObjectDispatcher $eventDispatcher
     * @param string $class
     * @param string $dataType
     * @param array $resolvable
     * @param array $attributeNames
     * @param \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[] $access
     * @param \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[] $visible
     * @param \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[] $defaults
     * @param callable[] $setFilters
     * @param callable[] $getFilters
     */
    public function __construct(string $class, string $dataType, array $attributeNames,
                                MixinSet $mixins = null,
                                array $setFilters = [],
                                array $getFilters = [])
    {
        $this->class = $class;
        $this->dataType = $dataType;
        $this->attributeNames = array_combine($attributeNames, $attributeNames);
        $this->setFilters = $setFilters;
        $this->getFilters = $getFilters;
        $this->setMixins($mixins ?? new MixinSet());
    }

    public function __call($name, $arguments)
    {
        return $this->mixins->call($name, $arguments);
    }

    /**
     * @return \Sayla\Objects\ObjectDispatcher
     */
    public function dispatcher(): \Sayla\Objects\ObjectDispatcher
    {
        return new ObjectDispatcher($this->getEventDispatcher(), $this->dataType);
    }

    public function getAttributeNames()
    {
        return $this->attributeNames;
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }

    protected function getEventDispatcher(): \Illuminate\Contracts\Events\Dispatcher
    {
        return $this->eventDispatcher ?? ($this->eventDispatcher = new SimpleEventDispatcher());
    }

    public function setEventDispatcher(\Illuminate\Contracts\Events\Dispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getGetFilters($attributeName)
    {
        return $this->getFilters[$attributeName] ?? [];
    }

    public function getMixin(string $name)
    {
        return $this->mixins[$name];
    }

    public function getObjectClass(): string
    {
        return $this->class;
    }

    public function getSetFilters($attributeName)
    {
        return $this->setFilters[$attributeName] ?? [];
    }

    public function hasMixin(string $mixinClass)
    {
        foreach ($this->mixins as $mixin)
            if (is_a($mixin, $mixinClass)) {
                return true;
            }
        return false;
    }

    /**
     * @return \Sayla\Objects\ObjectCollection
     */
    public function newCollection(): ObjectCollection
    {
        return $this->objectCollectionClass::makeObjectCollection($this->dataType, false, false);
    }

    public function serialize()
    {
        $props = [
            'attributeNames' => $this->attributeNames,
            'class' => $this->class,
            'dataType' => $this->dataType,
            'getFilters' => $this->getFilters,
            'setFilters' => $this->setFilters,
            'mixins' => $this->mixins
        ];
        return serialize($props);
    }

    /**
     * @param \Sayla\Util\Mixin\MixinSet $mixins
     */
    public function setMixins(\Sayla\Util\Mixin\MixinSet $mixins): void
    {
        $this->mixins = $mixins;
    }

    /**
     * @param \Sayla\Objects\ObjectCollection|string $objectCollectionClass
     */
    public function setObjectCollectionClass($objectCollectionClass): void
    {
        $this->objectCollectionClass = $objectCollectionClass;
    }

    public function unserialize($serialized)
    {
        $props = unserialize($serialized);
        $this->attributeNames = $props['attributeNames'];
        $this->class = $props['class'];
        $this->dataType = $props['dataType'];
        $this->getFilters = $props['getFilters'];
        $this->setFilters = $props['setFilters'];
        $this->mixins = $props['mixins'];
    }
}