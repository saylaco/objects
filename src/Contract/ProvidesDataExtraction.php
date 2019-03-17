<?php

namespace Sayla\Objects\Contract;

interface ProvidesDataExtraction
{
    /**
     * @param \Sayla\Objects\DataType\AttributesContext $context
     * @param callable $next
     * @return \Sayla\Objects\DataType\AttributesContext
     */
    public function extract($context, callable $next);
}