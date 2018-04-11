<?php

namespace Sayla\Objects\Transformers;

use GlobIterator;
use Sayla\Exception\Error;
use Sayla\Objects\Transformers\Transformer\DatetimeTransformer;

class ValueFactory
{
    /** @var \Psr\Container\ContainerInterface */
    protected $container;
    /** @var string[] */
    private static $sharedTransformerClasses = [];
    /** @var string[] */
    protected $transformerClasses = [];

    public function __construct(array $transformerClasses)
    {
        $this->transformerClasses = $transformerClasses;
    }

    public static function getTransformersInNamespace($class, string $subSpace = null,
                                                      string $fileSuffix = 'Transformer')
    {
        $reflector = new \ReflectionClass($class);
        $directory = dirname($reflector->getFileName());
        $namespace = $reflector->getNamespaceName();
        if ($subSpace) {
            $directory .= DIRECTORY_SEPARATOR . $subSpace;
            $namespace .= '\\' . $subSpace;
        }
        $transformers = [];
        $fileExtension = '.php';
        $iterator = new GlobIterator(
            $directory . DIRECTORY_SEPARATOR . '*' . $fileSuffix . $fileExtension,
            GlobIterator::KEY_AS_FILENAME
        );
        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            $transformers[lcfirst($item->getBasename($fileSuffix . $fileExtension))]
                = $namespace . '\\' . $item->getBasename($fileExtension);
        }
        return $transformers;
    }

    /**
     * @param string $valueTransformerClass
     * @param string|null $typeName
     * @return $this
     * @throws \Sayla\Exception\Error
     */
    public function addType(string $valueTransformerClass, string $typeName = null)
    {
        if (blank($typeName)) {
            $typeName = lcfirst(class_basename($valueTransformerClass));
        }
        if (isset($this->transformerClasses[$typeName])) {
            throw new Error('Transformer type already exists: ' . $typeName);
        }
        $this->transformerClasses[$typeName] = $valueTransformerClass;
        return $this;
    }

    /**
     * @param string $valueTransformerClass
     * @param string|null $typeName
     * @return $this
     * @throws \Sayla\Exception\Error
     */
    public static function shareType(string $valueTransformerClass, string $typeName = null)
    {
        if (blank($typeName)) {
            $typeName = lcfirst(class_basename($valueTransformerClass));
        }
        if (isset(self::$sharedTransformerClasses[$typeName])) {
            throw new Error('Transformer type is already shared: ' . $typeName);
        }
        self::$sharedTransformerClasses[$typeName] = $valueTransformerClass;
    }

    public function getTransformer($type, $options = null): ValueTransformer
    {
        $transformerClass = $this->getTransformerClass($type);
        if (isset($this->container) && $this->container->has($transformerClass)) {
            $valueTransformer = $this->container->get($transformerClass);
        } else {
            $valueTransformer = new $transformerClass();
        }
        if ($options) {
            if (!$options instanceof Options) {
                $options = new Options($options);
            }
            $valueTransformer->setOptions($options);
        }
        return $valueTransformer;
    }

    /**
     * @param string $type
     * @return string
     * @throws \Sayla\Exception\Error
     */
    public function getTransformerClass(string $type): string
    {
        if ($this->isInstanceType($type)) {
            return $this->transformerClasses[$type];
        }
        if (self::isSharedType($type)) {
            return self::$sharedTransformerClasses[$type];
        }
        if (ends_with($type, 'Stamp')) {
            return DatetimeTransformer::class;
        }
        throw new Error('Transformer type does not exist: ' . $type);
    }

    /**
     * @param string $type
     * @return bool
     */
    public function isSupportedType(string $type): bool
    {
        return  $this->isInstanceType($type) || self::isSharedType($type);
    }

    /**
     * @param string $type
     * @return bool
     */
    public function isInstanceType(string $type): bool
    {
        return isset($this->transformerClasses[$type]);
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function isSharedType(string $type): bool
    {
        return isset(self::$sharedTransformerClasses[$type]);
    }

    /**
     * @param string $valueTransformerClass
     * @param string|null $typeName
     * @return $this
     */
    public function overrideType(string $valueTransformerClass, string $typeName = null)
    {
        if (blank($typeName)) {
            $typeName = lcfirst(class_basename($valueTransformerClass));
        }
        $this->transformerClasses[$typeName] = $valueTransformerClass;
        return $this;
    }

    /**
     * @param \Psr\Container\ContainerInterface $container
     */
    public function setContainer(\Psr\Container\ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @param string $type
     * @return bool
     */
    public function typeResolvesToObject(string $type)
    {
        return $type == 'object' || $type == 'collection' || $type == 'datetime' || class_exists($type);
    }

}