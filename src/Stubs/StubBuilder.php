<?php

namespace Sayla\Objects\Stubs;

use Closure;
use Faker\Generator as Faker;
use InvalidArgumentException;
use Sayla\Objects\Contract\Keyable;
use Sayla\Objects\DataObject;
use Sayla\Objects\DataType\DataTypeManager;
use Sayla\Objects\ObjectCollection;
use Sayla\Objects\StorableTrait;

class StubBuilder
{
    /**
     * The states to apply.
     *
     * @var array
     */
    protected $activeStates = [];
    /**
     * The number of objects to build.
     *
     * @var int|null
     */
    protected $amount = null;
    /**
     * The object being built.
     *
     * @var string
     */
    protected $class;
    /**
     * The object definitions in the container.
     *
     * @var array
     */
    protected $definitions;
    /**
     * The Faker instance for the builder.
     *
     * @var \Faker\Generator
     */
    protected $faker;
    /**
     * The name of the object being built.
     *
     * @var string
     */
    protected $name = 'default';
    /**
     * The object states.
     *
     * @var array
     */
    protected $states;
    /**
     * @var \Sayla\Objects\DataType\DataTypeManager
     */
    private $dataTypeManager;

    /**
     * Create an new builder instance.
     *
     * @param string $class
     * @param string $name
     * @param array $definitions
     * @param array $states
     * @param \Faker\Generator $faker
     * @param \Sayla\Objects\DataType\DataTypeManager $dataTypeManager
     */
    public function __construct(string $class,
                                string $name,
                                array $definitions,
                                array $states,
                                Faker $faker,
                                DataTypeManager $dataTypeManager)
    {
        $this->name = $name;
        $this->class = $class;
        $this->faker = $faker;
        $this->states = $states;
        $this->definitions = $definitions;
        $this->dataTypeManager = $dataTypeManager;
    }

    /**
     * Apply the active states to the object definition array.
     *
     * @param array $definition
     * @param array $attributes
     * @return array
     */
    protected function applyStates(array $definition, array $attributes = [])
    {
        foreach ($this->activeStates as $state) {
            if (!isset($this->states[$this->class][$state])) {
                throw new InvalidArgumentException("Unable to locate [{$state}] state for [{$this->class}].");
            }

            $definition = array_merge($definition, call_user_func(
                $this->states[$this->class][$state],
                $this->faker, $attributes
            ));
        }

        return $definition;
    }

    /**
     * Create a collection of objects and persist them to the database.
     *
     * @param array $attributes
     * @return \Sayla\Objects\DataObject|\Sayla\Objects\DataObject[]
     */
    public function create(array $attributes = [])
    {
        $results = $this->make($attributes);

        if ($results instanceof DataObject) {
            $this->store(collect([$results]));
        } else {
            $this->store($results);
        }

        return $results;
    }

    /**
     * Expand all attributes to their underlying values.
     *
     * @param array $attributes
     * @return array
     */
    protected function expandAttributes(array $attributes)
    {
        foreach ($attributes as &$attribute) {
            if ($attribute instanceof Closure) {
                $attribute = $attribute($attributes);
            }

            if ($attribute instanceof static) {
                $attribute = $attribute->create()->getKey();
            }

            if ($attribute instanceof Keyable) {
                $attribute = $attribute->getKey();
            }
        }

        return $attributes;
    }

    /**
     * Get a raw attributes array for the object.
     *
     * @param array $attributes
     * @return mixed
     */
    protected function getRawAttributes(array $attributes = [])
    {
        $definition = call_user_func(
            $this->definitions[$this->class][$this->name],
            $this->faker, $attributes
        );

        return $this->expandAttributes(
            array_merge($this->applyStates($definition, $attributes), $attributes)
        );
    }

    /**
     * Create a object and persist it in the database if requested.
     *
     * @param array $attributes
     * @return \Closure
     */
    public function lazy(array $attributes = [])
    {
        return function () use ($attributes) {
            return $this->create($attributes);
        };
    }

    /**
     * Create a collection of objects.
     *
     * @param array $attributes
     * @return mixed
     */
    public function make(array $attributes = [])
    {
        if ($this->amount === null) {
            return $this->makeInstance($attributes);
        }

        if ($this->amount < 1) {
            return ObjectCollection::make();
        }
        $items = array_map(function () use ($attributes) {
            return $this->makeInstance($attributes);
        }, range(1, $this->amount));
        return ObjectCollection::make($items);
    }

    /**
     * Make an instance of the object with the given attributes.
     *
     * @param array $attributes
     * @return \Sayla\Objects\DataObject
     *
     * @throws \Sayla\Exception\Error
     */
    protected function makeInstance(array $attributes = [])
    {
        if (!isset($this->definitions[$this->class][$this->name])) {
            throw new InvalidArgumentException("Unable to locate factory with name [{$this->name}] [{$this->class}].");
        }

        return $this->dataTypeManager->get($this->class)->hydrate($this->getRawAttributes($attributes));
    }

    /**
     * Create an array of raw attribute arrays.
     *
     * @param array $attributes
     * @return mixed
     */
    public function raw(array $attributes = [])
    {
        if ($this->amount === null) {
            return $this->getRawAttributes($attributes);
        }

        if ($this->amount < 1) {
            return [];
        }

        return array_map(function () use ($attributes) {
            return $this->getRawAttributes($attributes);
        }, range(1, $this->amount));
    }

    /**
     * Set the states to be applied to the object.
     *
     * @param array|mixed $states
     * @return $this
     */
    public function states($states)
    {
        $this->activeStates = is_array($states) ? $states : func_get_args();

        return $this;
    }

    /**
     * Set the connection name on the results and store them.
     *
     * @param \Illuminate\Support\Collection $results
     * @return void
     */
    protected function store($results)
    {
        $results->each(function (Storable  $object, $i) use ($results) {
            if ($object->exists()) {
                $object->update();
            } else {
                $results->put($i, $object->create());
            }
        });
    }

    /**
     * Set the amount of objects you wish to create / make.
     *
     * @param int $amount
     * @return $this
     */
    public function times($amount)
    {
        $this->amount = $amount;

        return $this;
    }
}
