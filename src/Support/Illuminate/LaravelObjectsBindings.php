<?php

namespace Sayla\Objects\Support\Illuminate;

use Faker\Generator as FakerGenerator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Factory;
use Sayla\Objects\Attribute\PropertyType\OwnedDescriptorMixin;
use Sayla\Objects\DataType\DataTypeManager;
use Sayla\Objects\ObjectsBindings;
use Sayla\Objects\Stubs\StubFactory;
use Sayla\Objects\Validation\ValidationBuilder;
use Sayla\Support\Bindings\BindingSetBuilder;
use Sayla\Support\Bindings\Contract\RunsOnBoot;


class LaravelObjectsBindings extends ObjectsBindings implements RunsOnBoot
{
    /**
     * @param \Illuminate\Contracts\Foundation\Application $container
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function booting($container, $qualifiedAliases): void
    {
        $container->extend(DataTypeManager::class, function (DataTypeManager $manager, $app) {
            $manager->setDispatcher($app['events']);
            return $manager;
        });
        if ($bootValidationFactory = $this->option('bootValidationFactory')) {
            $bootValidationFactory = is_bool($bootValidationFactory) ? 'validator' : $bootValidationFactory;
            /** @var Factory $validator */
            $validator = $container->make($bootValidationFactory);
            $validator->extend('objExists', function ($attribute, $value, $args) use ($container, $qualifiedAliases) {
                /** @var DataTypeManager $dataTypeManager */
                $dataTypeManager = $container->get($qualifiedAliases['dataTypeManager']);
                /** @var \Sayla\Objects\Stores\StoreManager $store */
                return filled($value) ? $dataTypeManager
                    ->get($args[0])->getStoreStrategy()
                    ->exists($value) : false;
            }, 'Object not found.');
            ValidationBuilder::setSharedValidationFactory($validator);
        }
        if ($bootOwnerCallback = $this->option('bootOwnerCallback')) {
            OwnedDescriptorMixin::setDefaultUserAttributeCallback(function (string $attributeName) {
                /** @var \Illuminate\Auth\AuthManager $auth */
                $auth = app('auth');
                $guard = $auth->guard();
                $authenticatable = $guard->user();
                return $authenticatable->{$attributeName};
            });
        }
    }

    protected function configureOptions($optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'bootOwnerCallback' => true,
            'bootValidationFactory' => true,
            'stubsPath' => null
        ]);
        $optionsResolver->setAllowedTypes('bootValidationFactory', ['boolean', 'string']);
        $optionsResolver->setAllowedTypes('bootOwnerCallback', 'boolean');
        $optionsResolver->setAllowedTypes('stubsPath', 'string');
    }

    /**
     * @return array
     */
    protected function getBindingSet($setBuilder): array
    {
        parent::getBindingSet($setBuilder);
        $this->prepareLaravelBindings($setBuilder);
        return $setBuilder->getBindings();
    }

    protected function prepareLaravelBindings(BindingSetBuilder $setBuilder)
    {
        $setBuilder->add('objectStubs', StubFactory::class,
            function (Container $app) {
                $stubFactory = new StubFactory(
                    $app->make(FakerGenerator::class),
                    $app->make(DataTypeManager::class)
                );

                if (filled($stubsPath = $this->option('stubsPath'))) {
                    $stubFactory->load($stubsPath);
                }
                return $stubFactory;
            });
    }

    /**
     * @param bool $shouldBoot
     * @return $this
     */
    public function setBootValidationFactory(bool $shouldBoot)
    {
        $this->setOption('bootValidationFactory', $shouldBoot);
        return $this;
    }

    /**
     * @param string $laravelStubsPath
     * @return $this
     */
    public function setStubsPath(string $laravelStubsPath)
    {
        $this->setOption('stubsPath', $laravelStubsPath);
        return $this;
    }

}
