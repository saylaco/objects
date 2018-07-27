<?php

namespace Sayla\Objects\Transformers;

use Sayla\Exception\Error;
use Sayla\Objects\Exception\TransformationError;

class Transformer
{
    /**
     * @var ValueTransformerFactory
     */
    private static $factoryInstance;
    /**
     * @var string[]
     */
    private static $aliases = [
        'fk' => 'databaseKey',
        'pk' => 'databaseKey',
        'integer' => 'int',
    ];
    /**
     * Definitions for transforming data types
     * @var mixed[][]
     */
    protected $options = [];
    /**
     * @var bool
     */
    protected $skipNonAttributes = false;
    /**
     * @var bool
     */
    protected $skipObjectSmashing = false;
    /**
     * @var array
     */
    private $valueTransformers = [];
    /**
     * @var ValueTransformerFactory
     */
    private $valueFactory;

    /**
     * @param iterable $allOptions
     */
    public function __construct(iterable $allOptions = [])
    {
        $this->options = [];
        foreach ($allOptions as $name => $options) {
            $options = simple_value($options);
            $type = array_pull($options, 'type');
            $this->addAttribute($name, $type, $options);
        }
    }

    /**
     * @param string $name
     * @param array $optionsArray
     */
    public function addAttribute(string $name, string $type, array $optionsArray): void
    {
        $options = new Options($optionsArray);
        if (isset(self::$aliases[$type])) {
            $options['type'] = self::$aliases[$type];
        } else {
            $options['type'] = $type;
        }
        if ($options->alias) {
            $aliasOptions = $optionsArray;
            $aliasOptions['aliasOf'] = $name;
            $this->options[$options->alias] = new Options($aliasOptions);
        }
        $this->options[$name] = $options;
    }

    /**
     * @param \Sayla\Objects\Transformers\ValueTransformerFactory $resolver
     */
    public static function setValueFactory(ValueTransformerFactory $resolver)
    {
        self::$factoryInstance = $resolver;
    }

    /**
     * @param $attributes
     * @return mixed
     * @throws \Sayla\Exception\Error
     */
    public function buildAll($attributes)
    {
        if (count($this->options) == 0) {
            return $attributes;
        }
        foreach ($attributes as $k => $v)
            $attributes[$k] = $this->build($k, $v);
        return $attributes;
    }

    /**
     * @param string $key
     * @param null $value
     * @return mixed|null
     * @throws \Sayla\Exception\Error
     */
    public function build(string $key, $value = null)
    {
        try {
            if ($this->isNotTransformable($key)) {
                return $value;
            }
            return $this->callBuilder($key, $value);
        } catch (\Throwable $exception) {
            throw new Error("Failed transformation of \${$key}", $exception);
        }
    }

    /**
     * @param $key
     * @return bool
     */
    protected function isNotTransformable($key): bool
    {
        return ($this->skipNonAttributes && !$this->isAttribute($key))
            || ($this->isAttribute($key)
                && $this->skipObjectSmashing
                && in_array($this->getAttributeOptions($key)->type, ['objectCollection', 'object']));
    }

    /**
     * @param string $attr
     * @return bool
     */
    public function isAttribute(string $attr): bool
    {
        return isset($this->options[$attr]);
    }

    /**
     * @param string $attr
     * @return Options|Options[]
     * @throws \ErrorException
     */
    public function getAttributeOptions(string $attr = null)
    {
        if ($attr) {
            if (!$this->isAttribute($attr)) {
                throw new \ErrorException('Transformer is not configured for "' . $attr . '"');
            }
            return $this->options[$attr];
        }
        return $this->options;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function callBuilder(string $key, $value)
    {
        return call_user_func([$this->getValueTransformer($key), 'build'], $value);
    }


    /**
     * @param $key
     * @return \Sayla\Objects\Transformers\ValueTransformer
     * @throws \ErrorException
     */
    public function getValueTransformer($key): ValueTransformer
    {
        if (isset($this->valueTransformers[$key])) {
            return $this->valueTransformers[$key];
        }
        $options = $this->getAttributeOptions($key);
        return $this->valueTransformers[$key] = $this->getFactory()->getTransformer($options->type, $options);
    }

    /**
     * @return ValueTransformerFactory
     */
    public function getFactory(): ValueTransformerFactory
    {
        return $this->valueFactory ?? ValueTransformerFactory::getInstance();
    }

    /**
     * @param array $attributes
     * @return array
     * @throws \Sayla\Exception\Error
     */
    public function buildOnly(array $attributes): array
    {
        if (count($this->options) == 0) {
            return $attributes;
        }
        $built = [];
        foreach ($attributes as $k => $v) {
            if (isset($this->options[$k])) {
                $built[$k] = $this->build($k, $v);
            }
        }
        return $built;
    }

    /**
     * @param string $attributeName
     * @return \Closure
     */
    public function getBuildCallable(string $attributeName)
    {
        return function ($value, ...$args) use ($attributeName) {
            return $this->callBuilder($attributeName, $value);
        };
    }

    /**
     * @param string $attributeName
     * @return \Closure
     */
    public function getSmashCallable(string $attributeName)
    {
        return function ($value) use ($attributeName) {
            return $this->callSmasher($attributeName, $value);
        };
    }

    /**
     * @param string $key
     * @param $value
     * @return mixed
     */
    public function callSmasher(string $key, $value)
    {
        return call_user_func([$this->getValueTransformer($key), 'smash'], $value);
    }

    /**
     * @return \Illuminate\Support\Collection|\Sayla\Objects\Transformers\ValueTransformer[]
     */
    public function getValueTransformers()
    {
        $transformers = [];
        foreach ($this->getAttributeNames() as $key) {
            $transformers[$key] = $this->getValueTransformer($key);
        }
        return collect($transformers);
    }

    /**
     * @return string[]
     */
    public function getAttributeNames(): array
    {
        return array_keys($this->options);
    }

    /**
     * @return bool
     */
    public function isSkipNonAttributes(): bool
    {
        return $this->skipNonAttributes;
    }

    /**
     * @param ValueTransformerFactory $valueFactory
     * @return \Sayla\Objects\Transformers\Transformer
     */
    public function setFactory(?ValueTransformerFactory $valueFactory): self
    {
        $this->valueFactory = $valueFactory;
        return $this;
    }

    /**
     * @param bool $skipNonAttributes
     * @return Transformer
     */
    public function skipNonAttributes(bool $skipNonAttributes = true): self
    {
        $this->skipNonAttributes = $skipNonAttributes;
        return $this;
    }

    /**
     * @param bool $skipObjectSmashing
     * @return $this
     */
    public function skipObjectSmashing(bool $skipObjectSmashing = true)
    {
        $this->skipObjectSmashing = $skipObjectSmashing;
        return $this;
    }

    /**
     * @param $attributes
     * @return mixed
     * @throws \Sayla\Objects\Exception\TransformationError
     */
    public function smashAll($attributes)
    {
        if (count($this->options) == 0) {
            return $attributes;
        }
        foreach ($attributes as $k => $v) {
            $attributes[$k] = $this->smash($k, $v);
        }
        return $attributes;
    }

    /**
     * @param string $key
     * @param null $value
     * @return mixed|null
     * @throws \Sayla\Objects\Exception\TransformationError
     */
    public function smash(string $key, $value = null)
    {
        try {
            if ($this->isNotTransformable($key)) {
                return $value;
            }
            return $this->callSmasher($key, $value);
        } catch (\Throwable $e) {
            throw new TransformationError('Failed transformation of $' . $key, $e);
        }
    }

    /**
     * @param array $attributes
     * @return array
     * @throws \Sayla\Objects\Exception\TransformationError
     */
    public function smashOnly(array $attributes): array
    {
        if (count($this->options) == 0) {
            return $attributes;
        }
        $smashed = [];
        foreach ($attributes as $k => $v) {
            if (isset($this->options[$k])) {
                $smashed[$k] = $this->smash($k, $v);
            }
        }
        return $smashed;
    }

}