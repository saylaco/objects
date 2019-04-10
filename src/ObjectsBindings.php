<?php

namespace Sayla\Objects;

use Psr\Container\ContainerInterface;
use Sayla\Objects\DataType\DataTypeManager;
use Sayla\Objects\Stores\StoreManager;
use Sayla\Objects\Transformers\ValueTransformerFactory;
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
            'transformerValues' => [
                ValueTransformerFactory::class,
                function (ContainerInterface $container) {
                    $factory = ValueTransformerFactory::getInstance();
                    $factory->setContainer($container);
                    return $factory;
                },
                function (ContainerInterface $container) {
                    ValueTransformerFactory::setInstance($container->get(ValueTransformerFactory::class));
                }
            ]
        ];
    }
}
