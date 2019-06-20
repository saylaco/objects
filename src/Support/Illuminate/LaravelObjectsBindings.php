<?php

namespace Sayla\Objects\Support\Illuminate;

use Faker\Generator as FakerGenerator;
use Illuminate\Contracts\Container\Container;
use Sayla\Objects\ObjectsBindings;
use Sayla\Objects\Stubs\StubFactory;
use Sayla\Objects\Support\Illuminate\DbCnxt\DbTableStore;
use Sayla\Objects\Support\Illuminate\Eloquent\EloquentStore;
use Sayla\Support\Bindings\Contract\RunsOnBoot;


class LaravelObjectsBindings extends ObjectsBindings implements RunsOnBoot
{

    public function booting($container, $aliases): void
    {
        $bootstrapper = new Bootstrapper($container, $aliases['dataTypeManager']);

        if ($this->option('bootDispatcher')) {
            $bootstrapper->bootDispatcher();
        }
        if ($bootValidationFactory = $this->option('bootValidation')) {
            if (!is_bool($bootValidationFactory)) {
                $bootstrapper->bootValidation($container->make($bootValidationFactory));
            } else {
                $bootstrapper->bootValidation();
            }
        }
        if ($this->option('bootOwnerMixin')) {
            $bootstrapper->bootOwnerMixin();
        }

        $bootstrapper->getDataTypeManager()->getStoreManager()
            ->extend(DbTableStore::STORE_NAME, DbTableStore::class);
        
        if ($this->option('addEloquentStore')) {
            $bootstrapper->getDataTypeManager()->getStoreManager()
                ->extend(EloquentStore::STORE_NAME, EloquentStore::class);
        }
    }

    protected function configureOptions($optionsResolver): void
    {
        $optionsResolver->setDefaults([
            'bootOwnerMixin' => true,
            'bootValidation' => true,
            'addEloquentStore' => true,
            'stubsPath' => null,
            'bootDispatcher' => true
        ]);
        $optionsResolver->setAllowedTypes('bootValidation', ['boolean', 'string']);
        $optionsResolver->setAllowedTypes('bootOwnerMixin', 'boolean');
        $optionsResolver->setAllowedTypes('addEloquentStore', 'boolean');
        $optionsResolver->setAllowedTypes('bootDispatcher', 'boolean');
        $optionsResolver->setAllowedTypes('stubsPath', 'string');
    }

    protected function defineBindings($setBuilder)
    {
        parent::defineBindings($setBuilder);
        $setBuilder->add('objectStubs', StubFactory::class, function (Container $app) {
            $stubFactory = new StubFactory(
                $app->make(FakerGenerator::class),
                $app->make($this->dataTypeManager)
            );

            if (filled($stubsPath = $this->option('stubsPath'))) {
                $stubFactory->load($stubsPath);
            }
            return $stubFactory;
        });
    }

    public function setAddDynamoStore($value = false)
    {
        $this->setOption('addDynamoStore', $value);
        return $this;
    }

    public function setAddEloquentStore($value = true)
    {
        $this->setOption('addEloquentStore', $value);
        return $this;
    }

    public function setBootDispatcher($value = true)
    {
        $this->setOption('bootDispatcher', $value);
        return $this;
    }

    public function setBootOwnerMixin($value = true)
    {
        $this->setOption('bootOwnerMixin', $value);
        return $this;
    }

    public function setBootValidation($value = true)
    {
        $this->setOption('bootValidation', $value);
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
