<?php

namespace Sayla\Objects\Stores;

use Closure;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
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
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];
    /**
     * The array of resolved stores.
     *
     * @var array
     */
    protected $stores = [];
    private $driverOptionResolvers = [];
    /** @var callable */
    private $optionsLoader;
    private $storeOptions = [];

    /**
     * Create a new Cache manager instance.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }

    public function addStore(string $name, array $options)
    {
        return $this->storeOptions[$name] = $options;
    }

    protected function callCustomCreator(string $name, array $config): ObjectStore
    {
        return $this->customCreators[$config['driver']]($this->container, $config, $name);
    }

    protected function createDriver(string $driver): ObjectStore
    {
        $driverMethod = 'create' . ucfirst($driver) . 'Driver';
        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}();
        }
        throw new InvalidArgumentException("Driver [{$driver}] is not supported.");
    }

    protected function createEloquentDriver(): EloquentStore
    {
        return $this->container->make(EloquentStore::class);
    }

    protected function createFileDriver(): FileRepoStore
    {
        return $this->container->make(FileRepoStore::class);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param string $driver
     * @param \Closure $callback
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback->bindTo($this, $this);

        return $this;
    }

    /**
     * Unset the given driver instances.
     *
     * @param array|string|null $name
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

    public function get($name)
    {
        return $this->stores[$name] = $this->store($name);
    }

    public function getOptions($name): array
    {
        return $this->storeOptions[$name]['options'];
    }

    public function make(string $name, array $options)
    {
        if (!isset($this->stores[$name])) {
            $this->storeOptions[$name] = $options;
        }
        return $this->store($name);
    }

    protected function resolve(string $name): ObjectStore
    {
        if (!isset($this->storeOptions[$name]) && $this->optionsLoader) {
            $this->storeOptions[$name] = call_user_func($this->optionsLoader, $name);
        }

        $config = $this->storeOptions[$name] ?? null;
        if (empty($config)) {
            throw new InvalidArgumentException("Object store [{$name}] is not defined.");
        }
        $driver = array_pull($config, 'driver');
        if (empty($driver)) {
            throw new InvalidArgumentException("Object store [{$name}] does not have a defined driver.");
        }
        $store = isset($this->customCreators[$driver])
            ? $this->callCustomCreator($name, $config)
            : $this->createDriver($driver);
        if ($store instanceof ConfigurableStore) {
            if (!isset($this->driverOptionResolvers[$driver])) {
                $resolver = new OptionsResolver();
                $resolver->setRequired('name');
                $resolver->setRequired('objectClass');
                $store::defineOptions($resolver);
                $this->driverOptionResolvers[$driver] = $resolver;
            }
            $config['name'] = $name;
            $optionResolver = $this->driverOptionResolvers[$driver];
            $store->setOptions($name, $optionResolver->resolve($config));
        }
        return $store;
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

    public function store($name): ObjectStore
    {
        return $this->stores[$name] ?? $this->resolve($name);
    }
}
