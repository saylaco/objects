<?php

namespace Sayla\Objects\Contract;

/**
 * Interface AttributeResolver
 *
 * Example signature of resolve() method:
 *    public function resolve(\Sayla\Objects\DataObject $owningObject): mixed;
 * Example signature of resolveMany() method:
 *    public function resolveMany(\Sayla\Objects\ObjectCollection $objects): mixed[];
 *
 * @package Sayla\Objects\Contract
 * @method resolve($owningObject)
 * @method array resolveMany($owningObject)
 */
interface AttributeResolver
{
    /**
     * @return string
     */
    public function getAttributeName(): string;

    /**
     * @return string
     */
    public function getOwningObjectClass(): string;

    /**
     * @param string $name
     * @return mixed
     */
    public function setOwnerAttributeName(string $name);

    /**
     * @param string $objectClass
     * @return mixed
     */
    public function setOwnerObjectClass(string $objectClass);
}