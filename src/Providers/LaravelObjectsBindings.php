<?php

namespace Sayla\Objects\Providers;

use Faker\Generator as FakerGenerator;
use Illuminate\Contracts\Container\Container;
use Sayla\Objects\DataModel;
use Sayla\Objects\Inspection\ObjectDescriptors;
use Sayla\Objects\ObjectsBindings;
use Sayla\Objects\Pipes\ModelPipeManager;
use Sayla\Objects\Stubs\StubFactory;
use Sayla\Objects\Validation\ValidationBuilder;


class LaravelObjectsBindings extends ObjectsBindings
{
    public function booting()
    {
        if ($bootValidationFactory = $this->option('bootValidationFactory')) {
            $bootValidationFactory = is_bool($bootValidationFactory)
                ? \Illuminate\Contracts\Validation\Factory::class
                : $bootValidationFactory;
            ValidationBuilder::setSharedValidationFactory(
                \Illuminate\Container\Container::getInstance()->make($bootValidationFactory)
            );
        }
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
        return parent::getBindingSet() + $this->getLaravelBindings();
    }

    /**
     * @return array
     */
    protected function getLaravelBindings()
    {
        return [
            'modelPipes' => [
                ModelPipeManager::class,
                function ($container) {
                    return new ModelPipeManager($container);
                },
                function ($container) {
                    DataModel::setPipeManager($container->get(ModelPipeManager::class));
                }
            ],
            'objectStubs' => [
                StubFactory::class,
                function (Container $app) {
                    $stubFactory = new StubFactory(
                        $app->make(FakerGenerator::class),
                        $app->make(ObjectDescriptors::class)
                    );

                    if (filled($stubsPath = $this->option('stubsPath'))) {
                        $stubFactory->load($stubsPath);
                    }
                    return $stubFactory;
                }
            ]
        ];
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
