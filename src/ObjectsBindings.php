<?php

namespace Sayla\Objects;

use Psr\Container\ContainerInterface;
use Sayla\Objects\DataType\DataTypeManager;
use Sayla\Objects\Stores\StoreManager;
use Sayla\Objects\Transformers\TransformerFactory;
use Sayla\Support\Bindings\BindingProvider;


class ObjectsBindings extends BindingProvider
{
    /**
     * @return array
     */
    protected function getBindingSet(): array
    {
        return [
            'storeManager' => [
                StoreManager::class,
                function ($container) {
                    return $container->get(DataTypeManager::class)->getStoreManager();
                }
            ],
            'dataTypeManager' => [
                DataTypeManager::class,
                function ($container) {
                    return new DataTypeManager(new StoreManager($container));
                }
            ],
            'dataTransformer' => [
                TransformerFactory::class,
                function (ContainerInterface $container) {
                    $factory = new TransformerFactory(TransformerFactory::getNativeTransformers());
                    $factory->setContainer($container);
                    return $factory;
                }
            ]
        ];
    }
}
