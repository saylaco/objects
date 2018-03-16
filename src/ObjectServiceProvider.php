<?php

namespace Sayla\Objects;

use Sayla\Objects\Providers\LaravelObjectsBindings;
use Sayla\Support\Bindings\Laravel\LaravelRegistrar;
use Sayla\Support\Bindings\Laravel\LaravelServiceProvider;
use Sayla\Support\Bindings\Registrar;


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

    protected function getBindingProvider()
    {
        return (new LaravelObjectsBindings())
            ->setStubsPath($this->stubsPath ?? $this->app->databasePath('objectFactories'));
    }

}
