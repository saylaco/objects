<?php

namespace Sayla\Objects\Transformers;

use GlobIterator;
use Sayla\Exception\Error;
use Sayla\Objects\Contract\Serializes;
use Sayla\Objects\Contract\SerializesTrait;
use Sayla\Objects\Transformers\Transformer\DatetimeTransformer;

class ValueTransformerFactory implements Serializes
{
    use SerializesTrait;
    private static $instance;
    /** @var string[] */
    private static $sharedTransformerClasses = [];
    /** @var \Psr\Container\ContainerInterface */
    protected $container;
    /** @var string[] */
    protected $transformerClasses = [];

    public function __construct(array $transformerClasses)
    {
        $this->transformerClasses = $transformerClasses;
    }

    public static function getInstance(): ValueTransformerFactory
    {
        if (!isset(self::$instance)) {
            $transformersInDirectory = ValueTransformerFactory::getTransformersInDirectory(
                __DIR__ . DIRECTORY_SEPARATOR . 'Transformer',
                __NAMESPACE__ . '\\Transformer'
            );
            self::$instance = new self($transformersInDirectory);
        }
        return self::$instance;
    }

    public static function setInstance(ValueTransformerFactory $factory)
    {
        self::$instance = $factory;
    }

    /**
     * @param string $directory
     * @param string $namespace
     * @param string $fileSuffix
     * @return array
     */
    public static function getTransformersInDirectory(string $directory, string $namespace,
                                                      string $fileSuffix = null): array
    {
        $fileSuffix = $fileSuffix ?? 'Transformer';
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

    /**
     * @return iterable
     */
    public static function unserializableInstanceProperties(): iterable
    {
        return ['container'];
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
    public function isInstanceType(string $type): bool
    {
        return isset($this->transformerClasses[$type]);
    }

    /**
     * @param string $type
     * @return bool
     */
    public function isSupportedType(string $type): bool
    {
        return $this->isInstanceType($type) || self::isSharedType($type);
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