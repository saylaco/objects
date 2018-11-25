<?php

namespace Sayla\Objects\DataType;

use Sayla\Objects\Exception\AttributeResolverNotFound;
use Sayla\Objects\ObjectCollection;
use Sayla\Util\Mixin\MixinSet;

class DataTypeDescriptor
{
    /** @var \Sayla\Util\Mixin\MixinSet[] */
    private static $mixins = [];
    protected $resolvable = [];
    /** @var \Sayla\Objects\ObjectDispatcher */
    private $eventDispatcher;
    /** @var string|\Sayla\Objects\DataObject */
    private $class;
    /** @var string */
    private $dataType;
    private $attributeNames = [];
    /** @var \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[] */
    private $access;
    /** @var \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[] */
    private $visible;
    /** @var \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[] */
    private $defaults;
    /** @var \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[] */
    private $setFilters = [];
    /** @var \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[] */
    private $getFilters = [];

    /**
     * DataTypeDescriptor constructor.
     * @param \Sayla\Objects\ObjectDispatcher $eventDispatcher
     * @param \Sayla\Objects\DataObject|string $class
     * @param string $dataType
     * @param array $resolvable
     * @param array $attributeNames
     * @param \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[] $access
     * @param \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[] $visible
     * @param \Illuminate\Support\Collection|\Sayla\Objects\Contract\Property[] $defaults
     * @param callable[] $setFilters
     * @param callable[] $getFilters
     */
    public function __construct(\Sayla\Objects\ObjectDispatcher $eventDispatcher, string $class, string $dataType,
                                \Illuminate\Support\Collection $resolvable,
                                \Illuminate\Support\Collection $attributeNames,
                                \Illuminate\Support\Collection $access,
                                \Illuminate\Support\Collection $visible,
                                \Illuminate\Support\Collection $defaults,
                                MixinSet $mixins = null,
                                array $setFilters = [],
                                array $getFilters = [])
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->class = $class;
        $this->dataType = $dataType;
        $this->resolvable = $resolvable;
        $this->attributeNames = $attributeNames;
        $this->access = $access;
        $this->visible = $visible;
        $this->defaults = $defaults;
        $this->setFilters = $setFilters;
        $this->getFilters = $getFilters;
        $this->setMixins($mixins ?? new MixinSet());
    }

    /**
     * @param \Sayla\Util\Mixin\MixinSet $mixins
     */
    public function setMixins(\Sayla\Util\Mixin\MixinSet $mixins): void
    {
        self::$mixins[$this->getDataType()] = $mixins;
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }

    public function __call($name, $arguments)
    {
        return self::$mixins[$this->getDataType()]->call($name, $arguments);
    }

    public function getAttributeNames()
    {
        return $this->attributeNames;
    }

    public function getDefaultValues(): array
    {
        $defaultValues = [];
        foreach ($this->defaults as $k => $v) {
            $defaultValues[$k] = value($v);
        }
        return $defaultValues;
    }

    /**
     * @return \Sayla\Objects\ObjectDispatcher
     */
    public function getEventDispatcher(): \Sayla\Objects\ObjectDispatcher
    {
        return $this->eventDispatcher;
    }

    /**
     * @param \Sayla\Objects\ObjectDispatcher $eventDispatcher
     */
    public function setEventDispatcher(\Sayla\Objects\ObjectDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getGetFilters($attributeName)
    {
        return $this->getFilters[$attributeName] ?? [];
    }

    public function getObjectClass(): string
    {
        return $this->class;
    }

    public function getResolvable()
    {
        return $this->resolvable->keys()->all();
    }

    public function getSetFilters($attributeName)
    {
        return $this->setFilters[$attributeName] ?? [];
    }

    public function getVisible()
    {
        return $this->visible->keys()->all();
    }

    public function getWritable(): array
    {
        return $this->access->filter->writable->keys()->all();
    }

    public function hasResolver(string $attributeName)
    {
        return isset($this->resolvable[$attributeName]);
    }

    /**
     * @param $attributeName
     * @return bool
     */
    public function isAttribute($attributeName)
    {
        return isset($this->attributeNames[$attributeName]);
    }

    public function isHidden(string $attributeName)
    {
        return !$this->visible[$attributeName];
    }

    public function isReadable(string $attributeName)
    {
        return $this->access[$attributeName]['readable'] ?? false;
    }

    public function isVisible(string $attributeName)
    {
        return $this->visible[$attributeName];
    }

    public function isWritable(string $attributeName)
    {
        return $this->access[$attributeName]['writable'] ?? false;
    }

    /**
     * @param string $attributeName
     * @param ObjectCollection|iterable $objects
     * @return array
     * @throws \Sayla\Objects\Exception\HydrationError
     */
    public function resolveValues(string $attributeName, iterable $objects)
    {
        $resolver = $this->getResolver($attributeName);
        return $resolver->resolveMany($objects);
    }

    /**
     * @param string $attributeName
     * @return \Sayla\Objects\Contract\AttributeResolver
     * @throws \Sayla\Objects\Exception\AttributeResolverNotFound
     */
    public function getResolver(string $attributeName): \Sayla\Objects\Contract\AttributeResolver
    {
        if (!isset($this->resolvable[$attributeName])) {
            throw new AttributeResolverNotFound('Resolver not found for ' . $this->dataType . '.$' . $attributeName);
        }
        /** @var \Sayla\Objects\Contract\AttributeResolver $resolver */
        $resolver = $this->resolvable[$attributeName]['delegate'];
        return $resolver;
    }
}