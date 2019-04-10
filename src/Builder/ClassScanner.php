<?php

namespace Sayla\Objects\Builder;

use ReflectionClass;
use Sayla\Objects\Annotation\AnnotationReader;
use Sayla\Objects\Annotation\ClassAnnotation;
use Sayla\Objects\Annotation\DataTypeEntry;

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

        $reader = new AnnotationReader($reflectionClass);
        $reader->addResolver('datatype', DataTypeEntry::class);

        $annotations = $reader->getResult();

        foreach ($annotations as $i => $annotation) {
            if ($annotation instanceof ClassAnnotation) {
                $annotation->process($reflectionClass);
            }
        }

        foreach ($annotations as $i => $annotation) {
            if ($annotation instanceof DataTypeEntry) {
                if ($annotation->hasStore()) {
                    $builder->store($annotation->getStoreDriver(), $annotation->getStoreOptions());
                }
                unset($annotations[$i]);
                break;
            }
        }
        $attributes = [];
        foreach ($annotations->get('attr') as $attr) {
            $definition = $attr->getProperties();
            if (filled($attr->getModifier())) {
                $definition['type'] = $attr->getModifier();
            }
            $attributes[$attr->getValue()] = $definition;
        }
        $builder->attributes($attributes);

        return $builder;
    }

}