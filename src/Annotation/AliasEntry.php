<?php

namespace Sayla\Objects\Annotation;

use Sayla\Objects\Attribute\Resolver\AliasResolver;

class AliasEntry extends AnnoEntry
{
    protected function init()
    {
        parent::init();
        $this->properties['resolver'] = new AliasResolver(array_pull($this->properties, 'of'));
    }

}