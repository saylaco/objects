<?php

namespace Sayla\Objects\Contract;

interface ProvidesDataHydration
{
    /**
     * @param \Sayla\Objects\DataType\AttributesContext $context
     * @param callable $next
     * @return \Sayla\Objects\DataType\AttributesContext
     */
    public function hydrate($context, callable $next);
}