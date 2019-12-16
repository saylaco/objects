<?php

namespace Sayla\Objects;

use Psr\Container\ContainerInterface;
use Sayla\Helper\Data\ArrayObject;
use Sayla\Objects\DataType\DataTypeManager;
use Sayla\Objects\Stores\StoreManager;
use Sayla\Objects\Transformers\TransformerFactory;
use Sayla\Support\Bindings\BindingProvider;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ObjectsBindings extends BindingProvider
{
    /**
     * @param OptionsResolver $optionsResolver
     */
    protected function configureOptions($optionsResolver): void
    {
        // TODO: Implement configureOptions() method.
    }

    /**
     * @return array
     */
    protected function defineBindings($setBuilder)
    {
        $setBuilder->addInstance('dataTypeManagerLoader', 'dataTypeManagerLoader', function ($container) {
            $loadersAlias = $this->dataTypeLoaders;
            $typeLoaders = $container[$loadersAlias];
            return [
                'typeLoaders' => $typeLoaders,
                'load' => function (DataTypeManager $dataTypeManager, callable $onConfig = null) use (
                    $typeLoaders, $container
                ) {
                    /** @var \Sayla\Objects\DataType\DataTypeLoader[] $typeLoaders */
                    foreach ($typeLoaders as $loader) {
                        $configs = $loader->configure($dataTypeManager);
                        if ($onConfig) {
                            $onConfig($configs);
                        }
                    }
                },
                'discover' => function (callable $onDiscover) use ($typeLoaders) {
                    /** @var \Sayla\Objects\DataType\DataTypeLoader[] $typeLoaders */
                    foreach ($typeLoaders as $loader) {
                        $objs = $loader->getDiscoveredObjects();
                        $onDiscover($objs);
                    }
                }
            ];
        });

        $setBuilder->add('dataTypeLoaders', 'dataTypeLoaders', function () {
            return new ArrayObject();
        });
        $setBuilder->add('storeManager', StoreManager::class, function ($container) {
            return $container->get($this->dataTypeManager)->getStoreManager();
        });

        $setBuilder->add('dataTypeManager', DataTypeManager::class, function ($container) {
            return new DataTypeManager(new StoreManager($container));
        });

        $setBuilder->add('dataTransformer', TransformerFactory::class, function (ContainerInterface $container) {
            $factory = new TransformerFactory(TransformerFactory::getNativeTransformers());
            $factory->setContainer($container);
            return $factory;
        });
    }
}
