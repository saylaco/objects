<?php

namespace Sayla\Objects\Support\Illuminate;

use Faker\Generator as FakerGenerator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Factory;
use Sayla\Objects\Builder\Builder;
use Sayla\Objects\DataType\DataTypeManager;
use Sayla\Objects\ObjectsBindings;
use Sayla\Objects\Stubs\StubFactory;
use Sayla\Objects\Validation\ValidationBuilder;


class LaravelObjectsBindings extends ObjectsBindings
{
    public function booting()
    {
        if ($bootValidationFactory = $this->option('bootValidationFactory')) {
            $bootValidationFactory = is_bool($bootValidationFactory)
                ? Factory::class
                : $bootValidationFactory;
            ValidationBuilder::setSharedValidationFactory(
                \Illuminate\Container\Container::getInstance()->make($bootValidationFactory)
            );
        }

        Builder::addOptionSet(
            ['store.driver' => 'eloquent'],
            [
                'traits' => [
                    EloquentObjectTrait::class
                ]
            ]
        );
    }

    protected function configureOptions($optionsResolver): void
    {
        $optionsResolver->setDefaults(['bootValidationFactory' => true, 'stubsPath' => null]);
        $optionsResolver->setAllowedTypes('bootValidationFactory', ['boolean', 'string']);
        $optionsResolver->setAllowedTypes('stubsPath', 'string');
    }

    /**
     * @return array
     */
    protected function getBindingSet(): array
    {
        return $this->prepareLaravelBindings(parent::getBindingSet());
    }

    /**
     * @return array
     */
    protected function prepareLaravelBindings(array $bindings)
    {
        $bindings['objectStubs'] = [
            StubFactory::class,
            function (Container $app) {
                $stubFactory = new StubFactory(
                    $app->make(FakerGenerator::class),
                    $app->make(DataTypeManager::class)
                );

                if (filled($stubsPath = $this->option('stubsPath'))) {
                    $stubFactory->load($stubsPath);
                }
                return $stubFactory;
            }
        ];
        return $bindings;
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
