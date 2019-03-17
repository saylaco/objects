<?php

namespace Sayla\Objects\Stores;

use Illuminate\Contracts\Container\Container;
use Sayla\Objects\Contract\ConfigurableStore;
use Sayla\Objects\Contract\ObjectStore;
use Sayla\Objects\Stores\FileStore\FileRepoStore;
use Sayla\Objects\Support\Illuminate\EloquentStore;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoreManager
{
    private static $instance;
    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The array of resolved stores.
     *
     * @var array
     */
    protected $stores = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    private $storeOptions = [];

    private $driverOptionResolvers = [];
    /** @var callable */
    private $optionsLoader;

    /**
     * Create a new Cache manager instance.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public static function getInstance(): self
    {
        return self::$instance ?? (self::$instance = new self(\Illuminate\Container\Container::getInstance()));
    }

    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }

    protected function createDriver(string $driver): ObjectStore
    {
        $driverMethod = 'create' . ucfirst($driver) . 'Driver';
        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}();
        }
        throw new \InvalidArgumentException("Driver [{$driver}] is not supported.");
    }

    public function get($name)
    {
        return $this->stores[$name] = $this->store($name);
    }

    public function getOptions($name): array
    {
        return $this->storeOptions[$name]['options'];
    }

    public function addStore(string $name, array $options)
    {
        return $this->storeOptions[$name] = $options;
    }

    public function store($name): ObjectStore
    {
        return $this->stores[$name] ?? $this->resolve($name);
    }

    protected function resolve(string $name): ObjectStore
    {
        if (!$this->storeOptions[$name] && $this->optionsLoader) {
            $this->storeOptions[$name] = call_user_func($this->optionsLoader, $name);
        }

        $config = $this->storeOptions[$name] ?? null;
        if (empty($config)) {
            throw new \InvalidArgumentException("Object store [{$name}] is not defined.");
        }
        $driver = array_pull($config, 'driver');
        if (empty($driver)) {
            throw new \InvalidArgumentException("Object store [{$name}] does not have a defined driver.");
        }
        $store = isset($this->customCreators[$driver])
            ? $this->callCustomCreator($name, $config)
            : $this->createDriver($driver);
        if ($store instanceof ConfigurableStore) {
            if (!isset($this->driverOptionResolvers[$driver])) {
                $resolver = new OptionsResolver();
                $store::defineOptions($resolver);
                $this->driverOptionResolvers[$driver] = $resolver;
            }
            $optionResolver = $this->driverOptionResolvers[$driver];
            $store->setOptions($name, $optionResolver->resolve($config));
        }
        return $store;
    }

    protected function callCustomCreator(string $name, array $config): ObjectStore
    {
        return $this->customCreators[$config['driver']]($this->container, $config, $name);
    }

    protected function createFileDriver(): FileRepoStore
    {
        return $this->container->make(FileRepoStore::class);
    }

    protected function createEloquentDriver(): EloquentStore
    {
        return $this->container->make(EloquentStore::class);
    }


    /**
     * Unset the given driver instances.
     *
     * @param  array|string|null $name
     * @return $this
     */
    public function forget(string ...$names)
    {
        foreach ($names as $storeName) {
            if (isset($this->stores[$storeName])) {
                unset($this->stores[$storeName]);
            }
        }

        return $this;
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param  string $driver
     * @param  \Closure $callback
     * @return $this
     */
    public function extend($driver, \Closure $callback)
    {
        $this->customCreators[$driver] = $callback->bindTo($this, $this);

        return $this;
    }

    /**
     * @param callable $optionsLoader
     * @return $this
     */
    public function setOptionsLoader(callable $optionsLoader)
    {
        $this->optionsLoader = $optionsLoader;
        return $this;
    }
}
