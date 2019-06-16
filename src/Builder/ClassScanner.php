<?php

namespace Sayla\Objects\Builder;

use Illuminate\Container\Container;
use ReflectionClass;
use Sayla\Objects\Annotation\AliasEntry;
use Sayla\Objects\Annotation\AnnoEntry;
use Sayla\Objects\Annotation\AnnotationReader;
use Sayla\Objects\Annotation\ClassAnnotation;
use Sayla\Objects\Annotation\DataTypeEntry;
use Sayla\Objects\Attribute\AttributeResolverFactory;
use Sayla\Objects\Attribute\PropertyType\Type;
use Sayla\Objects\Contract\Attributes\AssociationResolver;
use Sayla\Objects\Contract\DataObject\ProvidesResolvers;
use Sayla\Objects\Contract\IDataObject;
use Sayla\Objects\DataObject;

class ClassScanner
{
    /** @var string */
    protected $annotationCacheDir;

    public function __construct(string $annotationCacheDir = null)
    {
        $this->annotationCacheDir = $annotationCacheDir;
    }

    public function __invoke(Builder $builder)
    {
        $reflectionClass = new ReflectionClass($builder->getObjectClass());
        $attributes = array_merge(
            $this->getInheritedAttributes($reflectionClass),
            $this->getClassAttributes($reflectionClass, function (DataTypeEntry $annotation) use ($builder) {
                if ($annotation->hasStore()) {
                    $builder->store($annotation->getStoreDriver(), $annotation->getStoreOptions());
                }
            }));

        $builder->attributes($attributes);

        return $builder;
    }

    /**
     * @param \Sayla\Objects\Annotation\AnnoEntry $attr
     * @param array $attributes
     * @return array
     */
    private function addAttributeDefinition(AnnoEntry $attr, array $attributes): array
    {
        $definition = $attr->getProperties();
        if (filled($attr->getModifier())) {
            $definition['type'] = $attr->getModifier();
        }
        $attributes[$attr->getValue()] = $definition;
        return $attributes;
    }

    private function getAttributeResolverFactory()
    {
        return $this->attributeResolverFactory ??
            $this->attributeResolverFactory = Container::getInstance()
                ->make(AttributeResolverFactory::class);
    }

    /**
     * @param \Sayla\Objects\Builder\Builder $builder
     * @param \ReflectionClass $reflectionClass
     * @return array
     * @throws \zpt\anno\ReflectorNotCommentedException
     */
    private function getClassAttributes(ReflectionClass $reflectionClass, callable $onDataTypeEntry = null): array
    {
        $reader = new AnnotationReader($reflectionClass);
        $reader->addResolver('datatype', DataTypeEntry::class);
        $reader->addResolver('alias', AliasEntry::class);

        $annotations = $reader->getResult();

        foreach ($annotations as $i => $annotation) {
            if ($annotation instanceof ClassAnnotation) {
                $annotation->process($reflectionClass);
            }
        }

        foreach ($annotations as $i => $annotation) {
            if ($annotation instanceof DataTypeEntry) {
                if ($onDataTypeEntry) {
                    $onDataTypeEntry($annotation);
                }
                unset($annotations[$i]);
                break;
            }
        }
        $attributes = [];
        foreach ($annotations->get('attr') as $attr) {
            $attributes = $this->addAttributeDefinition($attr, $attributes);
        }
        foreach ($annotations->get('alias') as $alias) {
            $attributes = $this->addAttributeDefinition($alias, $attributes);
        }
        if ($reflectionClass->implementsInterface(ProvidesResolvers::class)) {
            $attributeFactory = $this->getAttributeResolverFactory();
            $attributeResolvers = $reflectionClass->getMethod('getResolvers')->invoke(null, $attributeFactory);
            foreach ($attributeResolvers as $attributeName => $resolver) {
                $type = $resolver instanceof AssociationResolver ? $resolver->getAssociatedDataType() : 'object';
                if (isset($attributes[$attributeName])) {
                    $attributes[$attributeName]['resolver'] = $resolver;
                    if (!isset($attributes[$attributeName]['type'])
                        || $attributes[$attributeName]['type'] === Type::DEFAULT_TYPE) {
                        $attributes[$attributeName]['type'] = $type;
                    }
                } else {
                    $annoEntry = new AnnoEntry('association', $attributeName, $type, compact('resolver'));
                    $attributes = $this->addAttributeDefinition($annoEntry, $attributes);
                }
            }
        }
        return $attributes;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param array $attributes
     * @return array
     * @throws \zpt\anno\ReflectorNotCommentedException
     */
    private function getInheritedAttributes(ReflectionClass $reflectionClass): array
    {
        $attributes = [];
        $parentClass = $reflectionClass->getParentClass();
        while ($parentClass
            && $parentClass->implementsInterface(IDataObject::class)
            && $parentClass->getName() !== DataObject::class
        ) {
            $attributes = array_merge($attributes, $this->getClassAttributes($parentClass));
            $parentClass = $parentClass->getParentClass();
        }
        return $attributes;
    }

}