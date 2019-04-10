<?php

namespace Sayla\Objects\Stubs;

use ArrayAccess;
use Faker\Generator as Faker;
use Sayla\Objects\DataType\DataTypeManager;
use Symfony\Component\Finder\Finder;

class StubFactory implements ArrayAccess
{
    /** @var self */
    private static $instance;
    /**
     * The model definitions in the container.
     *
     * @var array
     */
    protected $definitions = [];
    /**
     * The Faker instance for the builder.
     *
     * @var \Faker\Generator
     */
    protected $faker;
    /**
     * The registered model states.
     *
     * @var array
     */
    protected $states = [];
    /**
     * @var \Sayla\Objects\DataType\DataTypeManager
     */
    private $dataTypeManager;

    /**
     * Create a new factory instance.
     *
     * @param \Faker\Generator $faker
     */
    public function __construct(Faker $faker, DataTypeManager $dataTypeManager)
    {
        $this->faker = $faker;
        if (!self::hasInstance()) {
            self::setInstance($this);
        }
        $this->dataTypeManager = $dataTypeManager;
    }

    /**
     * @return StubFactory
     */
    public static function getInstance(): StubFactory
    {
        return self::$instance;
    }

    /**
     * @param StubFactory $instance
     */
    public static function setInstance(StubFactory $instance): void
    {
        self::$instance = $instance;
    }

    /**
     * @return bool
     */
    public static function hasInstance(): bool
    {
        return isset(self::$instance);
    }

    /**
     * Create an instance of the given model and persist it to the database.
     *
     * @param string $class
     * @param array $attributes
     * @return mixed
     */
    public function create($class, array $attributes = [])
    {
        return $this->of($class)->create($attributes);
    }

    /**
     * Create an instance of the given model and type and persist it to the database.
     *
     * @param string $class
     * @param string $name
     * @param array $attributes
     * @return mixed
     */
    public function createAs($class, $name, array $attributes = [])
    {
        return $this->of($class, $name)->create($attributes);
    }

    /**
     * Define a class with a given set of attributes.
     *
     * @param string $class
     * @param callable $attributes
     * @param string $name
     * @return $this
     */
    public function define($class, callable $attributes, $name = 'default')
    {
        $this->definitions[$class][$name] = $attributes;

        return $this;
    }

    /**
     * Define a class with a given short-name.
     *
     * @param string $class
     * @param string $name
     * @param callable $attributes
     * @return $this
     */
    public function defineAs($class, $name, callable $attributes)
    {
        return $this->define($class, $attributes, $name);
    }

    /**
     * Load factories from path.
     *
     * @param string $path
     * @return $this
     */
    public function load($path)
    {
        $factory = $this;

        if (is_dir($path)) {
            foreach (Finder::create()->files()->name('*.php')->in($path) as $file) {
                require $file->getRealPath();
            }
        }

        return $factory;
    }

    /**
     * Create an instance of the given model.
     *
     * @param string $class
     * @param array $attributes
     * @return mixed
     */
    public function make($class, array $attributes = [])
    {
        return $this->of($class)->make($attributes);
    }

    /**
     * Create an instance of the given model and type.
     *
     * @param string $class
     * @param string $name
     * @param array $attributes
     * @return mixed
     */
    public function makeAs($class, $name, array $attributes = [])
    {
        return $this->of($class, $name)->make($attributes);
    }

    /**
     * Create a builder for the given model.
     *
     * @param string $class
     * @param string $name
     * @return \Sayla\Objects\Stubs\StubBuilder
     */
    public function of($class, $name = 'default')
    {
        return new StubBuilder($class, $name, $this->definitions, $this->states, $this->faker, $this->dataTypeManager);
    }

    /**
     * Determine if the given offset exists.
     *
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->definitions[$offset]);
    }

    /**
     * Get the value of the given offset.
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->make($offset);
    }

    /**
     * Set the given offset to the given value.
     *
     * @param string $offset
     * @param callable $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->define($offset, $value);
    }

    /**
     * Unset the value at the given offset.
     *
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->definitions[$offset]);
    }

    /**
     * Get the raw attribute array for a given model.
     *
     * @param string $class
     * @param array $attributes
     * @param string $name
     * @return array
     */
    public function raw($class, array $attributes = [], $name = 'default')
    {
        return array_merge(
            call_user_func($this->definitions[$class][$name], $this->faker), $attributes
        );
    }

    /**
     * Get the raw attribute array for a given named model.
     *
     * @param string $class
     * @param string $name
     * @param array $attributes
     * @return array
     */
    public function rawOf($class, $name, array $attributes = [])
    {
        return $this->raw($class, $attributes, $name);
    }

    /**
     * Define a state with a given set of attributes.
     *
     * @param string $class
     * @param string $state
     * @param callable $attributes
     * @return $this
     */
    public function state($class, $state, callable $attributes)
    {
        $this->states[$class][$state] = $attributes;

        return $this;
    }
}
