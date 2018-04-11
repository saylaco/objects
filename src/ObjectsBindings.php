<?php

namespace Sayla\Objects;

use Psr\Container\ContainerInterface;
use Sayla\Objects\Inspection\ObjectDescriptors;
use Sayla\Objects\Transformers\Transformer;
use Sayla\Objects\Transformers\ValueFactory;
use Sayla\Support\Bindings\BindingProvider;


class ObjectsBindings extends BindingProvider
{
    /**
     * @return array
     */
    protected function getBindingSet(): array
    {
        return [
            'objectDescriptors' => [
                ObjectDescriptors::class,
                function () {
                    return new ObjectDescriptors();
                },
                function ($container) {
                    DataObject::setDescriptors($container->get(ObjectDescriptors::class));
                }
            ],
            'resolverFactory' => [
                AttributeResolverFactory::class,
                function () {
                    return new AttributeResolverFactory();
                }
            ],
            'transformerValues' => [
                ValueFactory::class,
                function (ContainerInterface $container) {
                    $classes = ValueFactory::getTransformersInNamespace(ValueFactory::class, 'Transformer');
                    $factory = new ValueFactory($classes);
                    $factory->setContainer($container);
                    return $factory;
                },
                function (ContainerInterface $container) {
                    Transformer::setFactory($container->get(ValueFactory::class));
                }
            ]
        ];
    }
}
