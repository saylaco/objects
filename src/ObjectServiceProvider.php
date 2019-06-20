<?php

namespace Sayla\Objects;

use Sayla\Objects\Support\Illuminate\LaravelObjectsBindings;
use Sayla\Support\Bindings\Laravel\LaravelServiceProvider;


class ObjectServiceProvider extends LaravelServiceProvider
{
    protected $stubsPath = null;

    protected function bindingRegistrar()
    {
        return parent::bindingRegistrar()
            ->useSingletons()
            ->setAliasPrefix('sayla.')
            ->setTags(['saylaObjects']);
    }

    protected function getBindingProvider(): LaravelObjectsBindings
    {
        return (new LaravelObjectsBindings())
            ->setStubsPath($this->stubsPath ?? $this->app->databasePath('objectFactories'));
    }

}
