<?php

namespace Sayla\Objects\DataType;


use ArrayIterator;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use ReflectionClass;
use Sayla\Exception\Error;
use Sayla\Objects\Builder\ClassScanner;
use Sayla\Objects\Builder\DataTypeConfig;
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
    /** @var bool */
    protected $registersProviders = true;
    /** @var \Sayla\Objects\Stores\StoreManager */
    protected $storeManager;
    /** @var callable[] */
    private $addDataTypeCallbacks = [];
    private $aliases = [];
    /** @var \Sayla\Objects\Builder\ClassScanner */
    private $classScanner;
    /** @var RegistrarRepository[] */
    private $configRepos = [];
    /** @var DataType[] */
    private $dataTypes = [];

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

    public function addBuilderRepo(RegistrarRepository $cache)
    {
        $this->configRepos[] = $cache;
    }

    public function addClass(string $class, string $classFile = null)
    {
        $config = $this->makeTypeConfig($class, compact('classFile'));
        if (!$this->alwaysScanClasses) {
            $config->beforeBuild($this->classScanner);
        }
        return $this->addConfig($config);
    }

    protected function addConfig(DataTypeConfig $config)
    {
        if ($this->dispatcher) {
            $this->dispatcher->dispatch(self::ON_CONFIG, [$config]);
            $this->dispatcher->dispatch(self::ON_CONFIG . ":{$config->getName()}", [$config]);
        }
        if (!empty($config->getAlias()) && !isset($this->aliases[$config->getAlias()])) {
            $this->aliases[$config->getAlias()] = $config->getName();
        }

        if (!isset($this->aliases[$config->getObjectClass()])) {
            $this->aliases[$config->getObjectClass()] = $config->getName();
        }
        return $this->configs[] = $config;
    }

    public function addConfigured(array $options)
    {
        if (!isset($options['classFile'])) {
            $options['classFile'] = class_exists($options['objectClass'])
                ? self::classFilePath($options['objectClass'])
                : null;
        }
        $config = $this->makeTypeConfig($options['objectClass'], $options);
        return $this->addConfig($config->disableOptionsValidation());
    }

    /**
     * @param \Sayla\Objects\Builder\DataTypeConfig $config
     * @return DataType
     */
    protected function addDataType(DataTypeConfig $config): DataType
    {
        $options = $config->getOptions();
        $dataType = new DataType($options);
        $this->dataTypes[$dataType->getName()] = $dataType;
        if ($dataType->hasStore()) {
            $dataType->setStoreResolver($this->getStoreResolver());
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
        if ($this->dispatcher) {
            $this->dispatcher->dispatch(self::AFTER_ADD_DATATYPE, [$dataType]);
            $this->dispatcher->dispatch(self::AFTER_ADD_DATATYPE . ":{$dataType->getName()}", [$dataType]);
        }
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
            foreach ($this->configRepos as $provider)
                foreach ($provider->getAllOptions() as $options) {
                    $this->addConfigured($options);
                }
        }

        foreach ($this->configs as $config) {
            $objectClass = $config->getObjectClass();

            if (TransformerFactory::isSharedType($config->getName())) {
                continue;
            }
            $this->addAttributeType($config->getName(), $objectClass);
        }
        foreach ($this->configs as $config) {
            $this->addDataType($config);
        }
        if ($this->dispatcher) {
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
}