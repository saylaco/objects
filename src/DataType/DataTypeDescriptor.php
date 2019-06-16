<?php

namespace Sayla\Objects\DataType;

use Illuminate\Contracts\Events\Dispatcher;
use Sayla\Objects\ObjectCollection;
use Sayla\Objects\ObjectDispatcher;
use Sayla\Objects\SimpleEventDispatcher;
use Sayla\Util\Mixin\MixinSet;
use Serializable;

/**
 * Class DataTypeDescriptor
 * @mixin \Sayla\Objects\Attribute\PropertyType\AccessDescriptorMixin
 * @mixin \Sayla\Objects\Attribute\PropertyType\MapDescriptorMixin
 * @mixin \Sayla\Objects\Attribute\PropertyType\ResolverDescriptorMixin
 * @mixin \Sayla\Objects\Attribute\PropertyType\TransformationDescriptorMixin
 */
class DataTypeDescriptor implements Serializable
{
    /** @var \Sayla\Util\Mixin\MixinSet */
    protected $mixins;
    /** @var string|ObjectCollection $objectCollectionClass */
    protected $objectCollectionClass = ObjectCollection::class;
    /** @var string[] */
    private $attributeNames = [];
    /** @var string */
    private $class;
    /** @var \Illuminate\Contracts\Events\Dispatcher */
    private $eventDispatcher;
    /** @var string */
    private $name;

    /**
     * DataTypeDescriptor constructor.
     * @param \Sayla\Objects\ObjectDispatcher $eventDispatcher
     * @param string $name
     * @param string $dataType
     * @param array $attributeNames
     * @param \Illuminate\Support\Collection|\Sayla\Objects\Contract\Attributes\Property[] $access
     * @param \Illuminate\Support\Collection|\Sayla\Objects\Contract\Attributes\Property[] $visible
     * @param \Illuminate\Support\Collection|\Sayla\Objects\Contract\Attributes\Property[] $defaults
     */
    public function __construct(string $name, string $class, array $attributeNames, MixinSet $mixins = null)
    {
        $this->class = $class;
        $this->name = $name;
        $this->attributeNames = array_combine($attributeNames, $attributeNames);
        $this->setMixins($mixins ?? new MixinSet());
    }

    public function __call($name, $arguments)
    {
        return $this->mixins->call($name, $arguments);
    }

    /**
     * @return \Sayla\Objects\ObjectDispatcher
     */
    public function dispatcher(): ObjectDispatcher
    {
        return new ObjectDispatcher($this->getEventDispatcher(), $this->name);
    }

    public function getAttributeNames()
    {
        return $this->attributeNames;
    }

    public function getDataType(): string
    {
        return $this->name;
    }

    protected function getEventDispatcher(): Dispatcher
    {
        return $this->eventDispatcher ?? ($this->eventDispatcher = new SimpleEventDispatcher());
    }

    public function setEventDispatcher(Dispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getMixin(string $name)
    {
        return $this->mixins[$name] ?? $this->mixins[class_basename($name)];
    }

    /**
     * @return \Sayla\Util\Mixin\MixinSet
     */
    public function getMixins(): MixinSet
    {
        return $this->mixins;
    }

    /**
     * @param \Sayla\Util\Mixin\MixinSet $mixins
     */
    public function setMixins(MixinSet $mixins): void
    {
        $this->mixins = $mixins;
    }

    public function getObjectClass(): string
    {
        return $this->class;
    }

    public function hasMixin(string $mixinClassOrName)
    {
        foreach ($this->mixins as $mixinName => $mixin)
            if ($mixinName === $mixinClassOrName || is_a($mixin, $mixinClassOrName)) {
                return true;
            }
        return false;
    }

    /**
     * @return \Sayla\Objects\ObjectCollection
     */
    public function newCollection(): ObjectCollection
    {
        return $this->objectCollectionClass::makeObjectCollection($this->name, false, false);
    }

    public function serialize()
    {
        $props = [
            'attributeNames' => $this->attributeNames,
            'class' => $this->class,
            'dataType' => $this->name,
            'mixins' => $this->mixins
        ];
        return serialize($props);
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
        $this->name = $props['dataType'];
        $this->mixins = $props['mixins'];
    }
}