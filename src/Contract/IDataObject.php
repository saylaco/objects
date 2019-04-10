<?php

namespace Sayla\Objects\Contract;

use Sayla\Objects\DataType\DataType;
use Serializable;

interface IDataObject extends Attributable, Serializable
{
    public static function dataType(): DataType;

    public static function dataTypeName(): string;

    /**
     * @return \Sayla\Objects\DataType\DataTypeDescriptor
     */
    public static function descriptor();

    public function clearModifiedAttributeFlags(): void;

    public function getModifiedAttributeNames(): array;

    public function getModifiedAttributes(): array;

    /**
     * @param iterable $attributes
     * @return $this
     */
    public function init(iterable $attributes);

    public function isAttributeReadable(string $attributeName): bool;

    public function isAttributeWritable(string $attributeName): bool;

    public function isInitializing(): bool;

    public function isResolving(): bool;

    public function resolve(...$attributes);

    public function toScalarArray();

    public function toVisibleArray(): array;

    public function toVisibleObject();

    public function toVisibleScalarArray();
}