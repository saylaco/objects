<?php

namespace Sayla\Objects\Stores;

use Illuminate\Contracts\Container\Container;
use Sayla\Objects\Contract\ConfigurableStore;
use Sayla\Objects\Contract\ObjectStore;
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

    private $storeConfigs = [];

    private $driverOptionResolvers = [];

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

    public function addStore(string $name, string $driver, array $options)
    {
        return $this->storeConfigs[$name] = compact('driver', 'options');
    }

    public function store($name): ObjectStore
    {
        return $this->stores[$name] ?? $this->resolve($name);
    }

    protected function resolve(string $name): ObjectStore
    {
        $config = $this->storeConfigs[$name] ?? null;

        if (is_null($config)) {
            throw new \InvalidArgumentException("Object store [{$name}] is not defined.");
        }

        $store = isset($this->customCreators[$config['driver']])
            ? $this->callCustomCreator($name, $config)
            : $this->createDriver($config['driver']);
        if ($store instanceof ConfigurableStore) {
            if (!isset($this->driverOptionResolvers[$config['driver']])) {
                $resolver = new OptionsResolver();
                $store::defineOptions($resolver);
                $this->driverOptionResolvers[$config['driver']] = $resolver;
            }
            $optionResolver = $this->driverOptionResolvers[$config['driver']];
            $store->setOptions($name, $optionResolver->resolve($config['options']));
        }
        return $store;
    }

    protected function callCustomCreator(string $name, array $config): ObjectStore
    {
        return $this->customCreators[$config['driver']]($this->container, $config, $name);
    }

    protected function createArrayDriver(): ArrayStore
    {
        return $this->container->make(ArrayStore::class);
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
}
