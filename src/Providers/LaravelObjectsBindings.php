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
    protected $stubsPath;
    private $bootValidationFactory = false;

    public function booting()
    {
        if ($this->bootValidationFactory) {
            ValidationBuilder::setSharedValidationFactory(
                \Illuminate\Container\Container::getInstance()->make(\Illuminate\Contracts\Validation\Factory::class)
            );
        }
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
                    if ($this->stubsPath != null) {
                        $stubFactory->load($this->stubsPath);
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
        $this->bootValidationFactory = $shouldBoot;
        return $this;
    }

    /**
     * @param string $laravelStubsPath
     * @return $this
     */
    public function setStubsPath(string $laravelStubsPath)
    {
        $this->stubsPath = $laravelStubsPath;
        return $this;
    }

}
