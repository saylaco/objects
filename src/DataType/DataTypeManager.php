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
    use ResolvesSelf;
    const ON_BEFORE_INIT = 'dataTypes.beforeInit';
    const ON_INIT = 'dataTypes.init';
    private static $instance;
    /** @var bool */
    protected $alwaysScanClasses = false;
    /** @var callable */
    protected $builderResolver;
    /** @var Builder[] */
    protected $builders = [];
    /** @var \Illuminate\Contracts\Events\Dispatcher */
    protected $dispatcher;
    /** @var bool */
    protected $registersProviders = true;
    /** @var \Sayla\Objects\Stores\StoreManager */
    protected $storeManager;
    private $aliases = [];
    /** @var RegistrarRepository[] */
    private $builderRepos = [];
    /** @var \Sayla\Objects\Builder\ClassScanner */
    private $classScanner;
    /** @var DataType[] */
    private $dataTypes = [];
    /** @var callable[] */
    private $postAddDataType = [];

    public function __construct(StoreManager $storeManager)
    {
        $this->storeManager = $storeManager;
        $this->classScanner = new ClassScanner();
        if (!isset(self::$instance)) {
            self::$instance = $this;
        }
    }

    private static function classFilePath(string $className): string
    {
        $reflector = new ReflectionClass($className);
        return $reflector->getFileName();
    }

    protected static function resolutionBinding(): string
    {
        return self::class;
    }

    public function addAttributeType(string $name, string $objectClass = null)
    {
        $this->dataTypes[$name] = null;
        $defaultOptions = ['dataType' => $name, 'class' => $objectClass];
        TransformerFactory::forceShareType(ObjectTransformer::class, $name, $defaultOptions);
        return $this;
    }

    protected function addBuilder(Builder $builder)
    {
        if (!empty($builder->getAlias()) && !isset($this->aliases[$builder->getAlias()])) {
            $this->aliases[$builder->getAlias()] = $builder->getName();
        }

        if (!isset($this->aliases[$builder->getObjectClass()])) {
            $this->aliases[$builder->getObjectClass()] = $builder->getName();
        }
        return $this->builders[] = $builder;
    }

    public function addBuilderRepo(RegistrarRepository $cache)
    {
        $this->builderRepos[] = $cache;
    }

    public function addClass(string $class, string $classFile = null)
    {
        $builder = $this->makeBuilder($class, compact('classFile'));
        if (!$this->alwaysScanClasses) {
            $builder->beforeBuild($this->classScanner);
        }
        return $this->addBuilder($builder);
    }

    public function addConfigured(array $options)
    {
        if (!isset($options['classFile'])) {
            $options['classFile'] = class_exists($options['objectClass'])
                ? self::classFilePath($options['objectClass'])
                : null;
        }
        $builder = $this->makeBuilder($options['objectClass'], $options);
        return $this->addBuilder($builder->disableOptionsValidation());
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

    public function getObjectClass(string $name): string
    {
        return $this->get($name)->getDescriptor()->getObjectClass();
    }

    /**
     * @param string $name
     * @return \Sayla\Objects\Contract\Stores\Lookup
     * @throws \Sayla\Exception\Error
     */
    public function getObjectLookup(string $name): Lookup
    {
        /** @var \Sayla\Objects\Contract\DataObject\Lookable $objectClass */
        $objectClass = $this->get($name)->getDescriptor()->getObjectClass();
        return $objectClass::lookup();
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

    public function init()
    {
        if ($this->dispatcher) {
            $this->dispatcher->dispatch(self::ON_BEFORE_INIT, [$this]);
        }

        if ($this->registersProviders) {
            foreach ($this->builderRepos as $provider)
                foreach ($provider->getBuilders() as $builderArray) {
                    $this->addConfigured($builderArray);
                }
        }

        foreach ($this->builders as $builder) {
            $objectClass = $builder->getObjectClass();

            if (TransformerFactory::isSharedType($builder->getName())) {
                continue;
            }
            $this->addAttributeType($builder->getName(), $objectClass);
        }
        foreach ($this->builders as $builder) {
            $this->addDataType($builder);
        }
        if ($this->dispatcher) {
            $this->dispatcher->dispatch(self::ON_INIT);
        }
        return $this;
    }

    public function makeBuilder(string $objectClass, array $options = null): Builder
    {
        $builder = new Builder($objectClass, $options);

        if (!isset($options['classFile']) && class_exists($objectClass, false)) {
            $builder->classFile(self::classFilePath($objectClass));
        }

        if ($this->builderResolver) {
            $builder->runCallback($this->builderResolver);
        }

        if ($this->alwaysScanClasses && isset($builder->classFile)) {
            $builder->beforeBuild($this->classScanner);
        }

        if (!isset($this->aliases[$builder->getName()])) {
            $this->aliases[$builder->getName()] = $objectClass;
        }

        return $builder;
    }

    public function onAddDataType(callable $callback)
    {
        $this->postAddDataType[] = $callback;
        return $this;
    }

    public function setAlwaysScanClasses(bool $value)
    {
        $this->alwaysScanClasses = $value;
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
     * @param \Illuminate\Contracts\Events\Dispatcher $dispatcher
     * @return DataTypeManager
     */
    public function setDispatcher(Dispatcher $dispatcher): DataTypeManager
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    public function setRegistersProviders(bool $value)
    {
        $this->registersProviders = $value;
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