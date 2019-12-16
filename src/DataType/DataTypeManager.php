<?php

namespace Sayla\Objects\DataType;


use ArrayIterator;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use IteratorAggregate;
use MJS\TopSort\Implementations\StringSort;
use ReflectionClass;
use Sayla\Objects\Builder\ClassScanner;
use Sayla\Objects\Builder\DataTypeConfig;
use Sayla\Objects\Contract\DataObject\SupportsDataTypeManager;
use Sayla\Objects\Contract\DataTypeConfigCache;
use Sayla\Objects\Contract\Exception\DataTypeException;
use Sayla\Objects\Contract\Stores\Lookup;
use Sayla\Objects\Stores\StoreManager;
use Sayla\Objects\Transformers\Transformer\ObjectTransformer;
use Sayla\Objects\Transformers\TransformerFactory;
use Sayla\Support\Bindings\ResolvesSelf;

class DataTypeManager implements IteratorAggregate, Arrayable
{
    use ResolvesSelf;
    const AFTER_ADD_DATATYPE = 'dataTypes.added';
    const ON_BEFORE_INIT = 'dataTypes.beforeInit';
    const ON_CONFIG = 'dataTypes.configuring';
    const ON_INIT = 'dataTypes.init';
    private static $instance;
    /** @var bool */
    protected $alwaysScanClasses = false;
    /** @var callable */
    protected $configBuilder;
    /** @var DataTypeConfig[] */
    protected $configs = [];
    /** @var \Illuminate\Contracts\Events\Dispatcher */
    protected $dispatcher;
    protected $eventDispatchingEnabled = true;
    /** @var bool */
    protected $registersProviders = true;
    /**
     * @var \MJS\TopSort\Implementations\StringSort
     */
    protected $sorter;
    /** @var \Sayla\Objects\Stores\StoreManager */
    protected $storeManager;
    /** @var callable[] */
    private $addDataTypeCallbacks = [];
    private $aliases = [];
    /** @var \Sayla\Objects\Builder\ClassScanner */
    private $classScanner;
    /** @var DataTypeConfigCache[] */
    private $configRepos = [];
    /** @var DataType[] */
    private $dataTypes = [];

    public function __construct(StoreManager $storeManager)
    {
        $this->sorter = new StringSort();
        $this->sorter->setThrowCircularDependency(false);
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

    public function addClass(string $class, string $classFile = null, array $config = [])
    {
        $config = $this->makeTypeConfig($class, ($config + compact('classFile')));
        if (!$this->alwaysScanClasses) {
            $config->beforeBuild($this->classScanner);
        }
        return $this->addConfig($config);
    }

    protected function addConfig(DataTypeConfig $config)
    {
        if ($this->isDispatching()) {
            $this->dispatcher->dispatch(self::ON_CONFIG, [$config]);
            $this->dispatcher->dispatch(self::ON_CONFIG . ":{$config->getName()}", [$config]);
        }

        $alias = $config->getAlias();
        if (!empty($alias) && !isset($this->aliases[$alias])) {
            $this->aliases[$alias] = $config->getName();
        }

        $extends = $config->getExtends();
        $this->sorter->add($config->getName(), $extends ? Arr::wrap($this->getNameFromAlias($extends)) : []);

        if (!isset($this->aliases[$config->getObjectClass()])) {
            $this->aliases[$config->getObjectClass()] = $config->getName();
        }
        if (!TransformerFactory::isSharedType($config->getName())) {
            $this->addAttributeType($config->getName(), $config->getObjectClass());
        }

        return $this->configs[] = $config;
    }

    public function addConfigured(array $cachedConfig)
    {
        if (!isset($cachedConfig['classFile'])) {
            $cachedConfig['classFile'] = class_exists($cachedConfig['objectClass'])
                ? self::classFilePath($cachedConfig['objectClass'])
                : null;
        }
        $config = $this->makeTypeConfig($cachedConfig['objectClass'], $cachedConfig);
        return $this->addConfig($config->disableOptionsValidation());
    }

    /**
     * @param \Sayla\Objects\Builder\DataTypeConfig $config
     * @return DataType
     */
    protected function addDataType(DataTypeConfig $config): DataType
    {
        $options = $config->getOptions();
        if ($extends = $config->getExtends()) {
            $options = array_replace_recursive($this->findConfig($extends)->getOptions(), $options);
        }
        $dataType = new DataType($options);
        $this->dataTypes[$dataType->getName()] = $dataType;
        if ($dataType->hasStore()) {
            $dataType->setStoreResolver($this->getStoreResolver());
        }
        if (!TransformerFactory::isSharedType($config->getName())) {
            $this->addAttributeType($config->getName(), $config->getObjectClass());
        }
        if (filled($this->addDataTypeCallbacks)) {
            foreach ($this->addDataTypeCallbacks as $callback) {
                call_user_func($callback, $dataType);
            }
        }
        /** @var \Sayla\Objects\DataObject|string $objectClass */
        $objectClass = $dataType->getObjectClass();
        if (is_subclass_of($objectClass, SupportsDataTypeManager::class)) {
            $objectClass::setDataTypeManager($this);
        }
        $config->runAddDataType($dataType);
        if ($this->isDispatching()) {
            $this->dispatcher->dispatch(self::AFTER_ADD_DATATYPE, [$dataType]);
            $this->dispatcher->dispatch(self::AFTER_ADD_DATATYPE . ":{$dataType->getName()}", [$dataType]);
        }

        if (!$dataType->hasDispatcher() && isset($this->dispatcher)) {
            $dataType->setDispatcher($this->dispatcher);
        }
        return $dataType;
    }

    public function addTypeRepository(DataTypeConfigCache $cache)
    {
        $this->configRepos[] = $cache;
    }

    public function get(string $name): DataType
    {
        $name = $this->aliases[$name] ?? $name;
        if (!$this->has($name)) {
            if (class_exists($name)) {
                return $this->addDataType($this->addClass($name));
            }
            throw DataTypeException::notFound($name);
        }

        return $this->dataTypes[$name];
    }

    public function getAlias($type)
    {
        return array_search($type, $this->aliases) ?? $type;
    }

    public function getDataTypeNames()
    {
        return array_keys($this->dataTypes);
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

    public function getNameFromAlias($name): string
    {
        return array_search($name, $this->aliases) ?: $name;
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
        return $this->get($name)->getObjectLookup();
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
        if ($this->isDispatching()) {
            $this->dispatcher->dispatch(self::ON_BEFORE_INIT, [$this]);
        }

        $this->eventDispatchingEnabled = false;

        if ($this->registersProviders) {
            foreach ($this->configRepos as $provider)
                foreach ($provider->getAllDataTypeConfigs() as $cachedConfig) {
                    $this->addConfigured($cachedConfig);
                }
        }
        $nameMap = [];
        foreach ($this->configs as $config) {
            $nameMap[$config->getName()] = $config;
        }

        $sort = $this->sorter->sort();

        foreach ($sort as $name) {
            $this->addDataType($nameMap[$name]);
        }

        $this->eventDispatchingEnabled = true;

        if ($this->isDispatching()) {
            $this->dispatcher->dispatch(self::ON_INIT);
        }
        return $this;
    }

    public function makeTypeConfig(string $objectClass, array $options = null): DataTypeConfig
    {
        $config = new DataTypeConfig($objectClass, $options);

        if (!isset($options['classFile']) && class_exists($objectClass, false)) {
            $config->classFile(self::classFilePath($objectClass));
        }

        if ($this->configBuilder) {
            $config->runCallback($this->configBuilder);
        }

        if ($this->alwaysScanClasses && isset($config->classFile)) {
            $config->beforeBuild($this->classScanner);
        }

        if (!isset($this->aliases[$config->getName()])) {
            $this->aliases[$config->getName()] = $objectClass;
        }

        return $config;
    }

    public function onAddDataType(callable $callback)
    {
        $this->addDataTypeCallbacks[] = $callback;
        return $this;
    }

    public function onBeforeInit($listener)
    {
        if (!$this->dispatcher) return;
        $this->dispatcher->listen(self::ON_BEFORE_INIT, $listener);
    }

    public function onConfiguration($listener, string $name = null)
    {
        if (!$this->dispatcher) return;
        if ($name) {
            $this->dispatcher->listen(self::ON_CONFIG . ":{$name}", $listener);
        }
        $this->dispatcher->listen(self::ON_CONFIG, $listener);
    }

    public function onDataTypeAdded($listener, string $name = null)
    {
        if (!$this->dispatcher) return;
        if ($name) {
            $this->dispatcher->listen(self::AFTER_ADD_DATATYPE . ":{$name}", $listener);
        }
        $this->dispatcher->listen(self::AFTER_ADD_DATATYPE, $listener);
    }

    public function onInit($listener)
    {
        if (!$this->dispatcher) return;
        $this->dispatcher->listen(self::ON_INIT, $listener);
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
    public function setConfigBuilder(callable $callback)
    {
        $this->configBuilder = $callback;
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

    /**
     * @param string $name
     * @return \Sayla\Objects\Builder\DataTypeConfig
     * @throws \Sayla\Objects\Contract\Exception\DataTypeException
     */
    private function findConfig(string $name): DataTypeConfig
    {
        $name = $this->getNameFromAlias($name);
        foreach ($this->configs as $config)
            if ($config->getName() === $name) {
                return $config;
            }
        throw new DataTypeException('Not found: ' . $name);
    }

    private function isDispatching()
    {
        return $this->dispatcher && $this->eventDispatchingEnabled;
    }
}