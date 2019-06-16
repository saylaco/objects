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
    protected function getBindingSet($setBuilder): array
    {
        $setBuilder->add('storeManager', StoreManager::class, function ($container) {
            return $container->get(DataTypeManager::class)->getStoreManager();
        });

        $setBuilder->add('dataTypeManager', DataTypeManager::class, function ($container) {
            return new DataTypeManager(new StoreManager($container));
        });

        $setBuilder->add('dataTransformer', TransformerFactory::class, function (ContainerInterface $container) {
            $factory = new TransformerFactory(TransformerFactory::getNativeTransformers());
            $factory->setContainer($container);
            return $factory;
        });
        
        return $setBuilder->getBindings();
    }
}
