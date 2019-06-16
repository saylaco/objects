<?php

namespace Sayla\Objects\DataType;


use ArrayIterator;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use ReflectionClass;
use Sayla\Exception\Error;
use Sayla\Objects\Builder\Builder;
use Sayla\Objects\Builder\ClassScanner;
use Sayla\Objects\Contract\DataObject\SupportsDataTypeManager;
use Sayla\Objects\Contract\RegistrarRepository;
use Sayla\Objects\Contract\Stores\Lookup;
use Sayla\Objects\Stores\StoreManager;
use Sayla\Objects\Transformers\Transformer\ObjectTransformer;
use Sayla\Objects\Transformers\TransformerFactory;
use Sayla\Support\Bindings\ResolvesSelf;

class DataTypeManager implements IteratorAggregate, Arrayable
{
    private static $instance;
    /** @var callable */
    protected $builderResolver;
    /** @var Builder[] */
    protected $builders = [];
    /** @var \Sayla\Objects\Stores\StoreManager */
    protected $storeManager;
    private $aliases = [];
    /** @var RegistrarRepository[] */
    private $builderRepos = [];
    /** @var DataType[] */
    private $dataTypes = [];
    /** @var callable[] */
    private $postAddDataType = [];

    public function __construct(StoreManager $manager)
    {
        $this->storeManager = $manager;
        if (!isset(self::$instance)) {
            self::$instance = $this;
        }
    }

    private static function classFilePath(string $className): string
    {
        $reflector = new ReflectionClass($className);
        return $reflector->getFileName();
    }

    public static function getInstance(): self
    {
        return self::$instance ?? (self::$instance = Container::getInstance()->make(self::class));
    }

    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }

    public function addAttributeType(string $name, string $objectClass = null)
    {
        $this->dataTypes[$name] = null;
        $defaultOptions = ['dataType' => $name, 'class' => $objectClass];
        ValueTransformerFactory::forceShareType(ObjectTransformer::class, $name, $defaultOptions);
        return $this;
    }

    protected function addBuilder(Builder $builder)
    {
        return $this->builders[] = $builder;
    }

    public function addBuilderRepo(RegistrarRepository $cache)
    {
        $this->builderRepos[] = $cache;
    }

    public function addClass(string $class, string $classFile = null)
    {
        $name = $name ?? $class;
        if (!isset($this->aliases[$class])) {
            $this->aliases[$class] = $name;
        }
        $builder = $this->makeBuilder($class, $name, $classFile);
        $builder->beforeBuild(new ClassScanner());
        return $this->addBuilder($builder);
    }

    public function addConfigured(array $options)
    {
        $builder = Builder::makeFromOptions($options);
        if ($this->builderResolver) {
            $builder->runCallback($this->builderResolver);
        }
        return $this->addBuilder($builder);
    }

    /**
     * @param \Sayla\Objects\Builder\Builder $builder
     * @return DataType
     */
    protected function addDataType(Builder $builder): DataType
    {
        $options = $builder->getOptions();
        $dataType = new DataType($options);
        $this->dataTypes[$dataType->getName()] = $dataType;
        if ($dataType->hasStore()) {
            $dataType->setStoreResolver($this->getStoreResolver());
        }
        if (filled($this->postAddDataType)) {
            foreach ($this->postAddDataType as $callback) {
                call_user_func($callback, $dataType);
            }
        }
        /** @var \Sayla\Objects\DataObject|string $objectClass */
        $objectClass = $dataType->getObjectClass();
        if (is_subclass_of($objectClass, SupportsDataTypeManager::class)) {
            $objectClass::setDataTypeManager($this);
        }
        $builder->runAddDataType($dataType);
        return $dataType;
    }

    public function get(string $name): DataType
    {
        $name = $this->aliases[$name] ?? $name;
        if (!$this->has($name)) {
            if (class_exists($name)) {
                return $this->addDataType($this->addClass($name));
            }
            throw new Error('Data type not found - ' . $name);
        }

        return $this->dataTypes[$name];
    }

    public function getDescriptor(string $name): DataTypeDescriptor
    {
        return clone $this->get($name)->getDescriptor();
    }

    /**
     * @return \ArrayIterator|DataType
     */
    public function getIterator()
    {
        return new ArrayIterator($this->dataTypes);
    }

    /**
     * @return \Sayla\Objects\Stores\StoreManager
     */
    public function getStoreManager(): StoreManager
    {
        return $this->storeManager;
    }

    /**
     * @param \Sayla\Objects\Stores\StoreManager $storeManager
     */
    public function setStoreManager(StoreManager $storeManager): void
    {
        $this->storeManager = $storeManager;
    }

    /**
     * @return \Closure
     */
    protected function getStoreResolver(): Closure
    {
        static $resolver;
        return $resolver ?? $resolver = function (array $options, string $name) {
                return $this->storeManager->make($name, $options);
            };
    }

    public function has(string $name): bool
    {
        return isset($this->dataTypes[$name]) || isset($this->aliases[$name]);
    }

    public function init(bool $registerProviders = true)
    {
        if ($registerProviders) {
            foreach ($this->builderRepos as $provider)
                foreach ($provider->getBuilders() as $builderArray)
                    $this->addConfigured($builderArray);
        }

        foreach ($this->builders as $builder) {
            $objectClass = $builder->getObjectClass();

            if (!isset($this->aliases[$objectClass])) {
                $this->aliases[$objectClass] = $builder->getName();
            }

            if (ValueTransformerFactory::isSharedType($builder->getName())) {
                continue;
            }
            $this->addAttributeType($builder->getName(), $objectClass);
        }

        foreach ($this->builders as $builder) {
            $this->addDataType($builder);
        }
        return $this;
    }

    public function makeBuilder(string $objectClass, string $name = null, string $classFile = null): Builder
    {
        $builder = new Builder($objectClass, $name);
        if ($this->builderResolver) {
            $builder->runCallback($this->builderResolver);
        }
        $builder->classFile($classFile ?? (class_exists($objectClass,
                false) ? self::classFilePath($objectClass) : null));
        return $builder;
    }

    public function onAddDataType(callable $callback)
    {
        $this->postAddDataType[] = $callback;
        return $this;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function setBuilderResolver(callable $callback)
    {
        $this->builderResolver = $callback;
        return $this;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->dataTypes;
    }
}