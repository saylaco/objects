<?php

namespace Sayla\Objects\Support\Illuminate;

use Illuminate\Contracts\Container\Container;
use Sayla\Objects\Attribute\PropertyType\OwnedDescriptorMixin;
use Sayla\Objects\DataType\DataTypeManager;
use Sayla\Objects\Validation\ValidationBuilder;

class Bootstrapper
{

    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    private $container;
    /**
     * @var string
     */
    private $dataManagerBinding;

    /**
     * @var string
     */
    private $validatorBinding;

    public function __construct(Container $container,
                                string $dataManagerBinding = 'dataTypeManager',
                                string $validatorBinding = 'validator')
    {

        $this->container = $container;
        $this->dataManagerBinding = $dataManagerBinding;
        $this->validatorBinding = $validatorBinding;
    }

    public function bootDispatcher($dispatcher = null)
    {
        $this->container->extend($this->dataManagerBinding,
            function (DataTypeManager $manager, $app) use ($dispatcher) {
                $manager->setDispatcher($dispatcher ?? $app['events']);
                return $manager;
            });
    }

    public function bootOwnerMixin(string $guard = null)
    {
        /** @var \Illuminate\Auth\AuthManager $auth */
        $auth = $this->container->make('auth');
        OwnedDescriptorMixin::setDefaultUserAttributeCallback(function (string $attributeName) use ($guard, $auth) {
            $guard = $auth->guard($guard);
            $authenticatable = $guard->user();
            return isset($authenticatable) ? $authenticatable->{$attributeName} : null;
        });
    }

    public function bootValidation($validator = null)
    {
        if (!$validator) /** @var \Illuminate\Validation\Factory $validator */ {
            $validator = $this->container->make($this->validatorBinding);
        }
        $validator->extend('objExists', function ($attribute, $value, $args) {
            /** @var \Sayla\Objects\Stores\StoreManager $store */
            return filled($value) ? $this->getDataTypeManager()
                ->get($args[0])->getObjectLookup()->exists($value) : false;
        }, 'Object not found.');

        ValidationBuilder::setSharedValidationFactory($validator);
    }

    public function getDataTypeManager(): DataTypeManager
    {
        return $this->container->get($this->dataManagerBinding);
    }
}