<?php

namespace Sayla\Objects\Annotation;

use ReflectionClass;

class DataTypeEntry extends AnnoEntry implements ClassAnnotation
{
    /** @var string */
    protected $objectName;
    /** @var string */
    protected $storeDriver;

    /**
     * @return string
     */
    public function getObjectName(): string
    {
        return $this->objectName;
    }

    /**
     * @return string
     */
    public function getStoreDriver(): string
    {
        return $this->storeDriver;
    }

    /**
     * @return mixed[]
     */
    public function getExtends(): ?string
    {
        return ltrim($this->properties['extends'] ?? null, '\\');
    }

    /**
     * @return mixed[]
     */
    public function getStoreOptions(): array
    {
        return $this->properties['store'] ?? [];
    }

    public function hasStore(): bool
    {
        return $this->storeDriver !== null;
    }

    public function process(ReflectionClass $class)
    {
        $this->objectName = $this->properties['name'] ?? class_basename($class->name);
        if (isset($this->properties['store'])) {
            if (is_string($this->properties['store'])) {
                $this->storeDriver = $this->properties['store'];
                $this->properties['store'] = [];
            } else {
                $this->storeDriver = array_pull($this->properties['store'], 'driver', null);
            }
        }
    }
}