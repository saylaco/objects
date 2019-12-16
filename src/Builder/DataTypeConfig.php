<?php

namespace Sayla\Objects\Builder;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use Sayla\Objects\Contract\DataObject\ResponsableObject;
use Sayla\Objects\DataType\DataType;
use Sayla\Objects\ObjectDispatcher;
use Sayla\Objects\Transformers\TransformerFactory;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @property-read array store
 * @property-read string name
 * @property-read string objectClass
 * @property-read \Sayla\Objects\Transformers\TransformerFactory transformerFactory
 * @property-read array attributes
 * @property-read ObjectDispatcher objectDispatcher
 * @property-read string classFile
 */
class DataTypeConfig
{
    private static $optionSets = [];
    /** @var callable[] */
    private $onAddDataTypeCallbacks = [];
    /** @var array */
    private $options;
    /** @var callable */
    private $optionsCallback;
    /** @var callable[] */
    private $postBuildCallbacks = [];
    /** @var callable[] */
    private $preBuildCallbacks = [];

    private $resolveOptions = true;

    /**
     * DataTypeBuilder constructor.
     * @param string $objectClass
     */
    public function __construct(string $objectClass, array $options = null)
    {
        $this->options = $options ?? [];
        $this->options['objectClass'] = $objectClass;
        if (empty($this->options['name'])) {
            $this->options['name'] = $objectClass;
        }
        if (empty($this->options['alias'])) {
            $alias = $this->options['name'];
            $this->options['alias'] = self::makeAlias($alias);
        }
    }

    public static function addOptionSet(array $matcher, array $options)
    {
        self::$optionSets[] = compact('matcher', 'options');
    }

    /**
     * @param $alias
     * @return mixed
     */
    protected static function makeAlias($alias): string
    {
        return str_replace('\\', '', Str::after($alias, 'Model\\') ?: $alias);
    }

    /**
     * @param string $optionName
     * @param $optionValue
     * @return $this
     */
    public function __call(string $optionName, $optionValues)
    {
        $this->options[$optionName] = $optionValues[0] ?? null;
        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->options[$key];
    }

    /**
     * @param string $key
     * @return bool
     */
    public function __isset(string $key)
    {
        return isset($this->options[$key]);
    }

    public function afterBuild(callable $callback)
    {
        $this->postBuildCallbacks[] = $callback;
        return $this;
    }

    public function alias(string $alias)
    {
        $this->options['alias'] = $alias;
        return $this;
    }

    /**
     * @param array $attributeDefinitions
     * @return $this
     */
    public function attributes(array $attributeDefinitions)
    {
        $this->options[__FUNCTION__] = $attributeDefinitions;
        return $this;
    }

    public function beforeBuild(callable $callback)
    {
        $this->preBuildCallbacks[] = $callback;
        return $this;
    }

    /**
     * @param string $classFile
     * @return $this
     */
    public function classFile(?string $classFile)
    {
        $this->options[__FUNCTION__] = $classFile;
        return $this;
    }

    public function disableOptionsValidation()
    {
        $this->resolveOptions = false;
        return $this;
    }

    public function enableOptionsValidation()
    {
        $this->resolveOptions = true;
        return $this;
    }

    public function extends(?string $extends)
    {
        $this->options[__FUNCTION__] = $extends;
        return $this;
    }

    public function getExtends(): ?string
    {
        return $this->options['extends'] ?? null;
    }


    public function getAlias(): string
    {
        return $this->options['alias'];
    }

    public function getName(): string
    {
        return $this->options['name'];
    }

    public function getObjectClass(): string
    {
        return $this->options['objectClass'];
    }

    public function getOptions(): array
    {
        if (filled($this->preBuildCallbacks)) {
            foreach ($this->preBuildCallbacks as $buildCallback) {
                call_user_func($buildCallback, $this);
            }
        }

        $options = $this->options;

        if ($this->resolveOptions) {
            foreach (self::$optionSets as $optionSet) {
                foreach ($optionSet['matcher'] as $k => $v) {
                    if (array_get($options, $k) !== $v) {
                        continue 2;
                    }
                }
                $options = array_merge($optionSet['options'], $options);
            }

            $options = $this->getOptionResolver()->resolve($options);
        }

        if (filled($this->postBuildCallbacks)) {
            foreach ($this->postBuildCallbacks as $buildCallback) {
                $options = call_user_func($buildCallback, $options) ?? $options;
            }
        }

        if (isset($this->optionsCallback)) {
            $options = call_user_func($this->optionsCallback, $options) ?? $options;
        }
        return $options;
    }


    /**
     * @return array
     */
    public function getStoreOptions(): ?array
    {
        return $this->options['store'] ?? [];
    }

    public function name(string $name)
    {
        $this->options[__FUNCTION__] = $name;
        return $this;
    }

    /**
     * @param \Sayla\Objects\ObjectDispatcher $objectDispatcher
     * @return $this
     */
    public function objectDispatcher(ObjectDispatcher $objectDispatcher)
    {
        $this->options[__FUNCTION__] = $objectDispatcher;
        return $this;
    }

    public function onAddDataType(callable $callback)
    {
        $this->onAddDataTypeCallbacks[] = $callback;
        return $this;
    }

    public function onOptionsResolution(callable $callback)
    {
        $this->optionsCallback = $callback;
        return $this;
    }

    public function propertyTypeOptions(array $options)
    {
        $this->options[__FUNCTION__] = $options;
        return $this;
    }

    public function runAddDataType(DataType $dataType)
    {
        foreach ($this->onAddDataTypeCallbacks as $callback)
            call_user_func($callback, $dataType);
        return $this;
    }

    /**
     * @param callable $callable
     * @return $this
     */
    public function runCallback(callable $callable)
    {
        call_user_func($callable, $this, $this->options['objectClass']);
        return $this;
    }

    public function store(string $driver, array $options = [], string $storeName = null)
    {
        $options['name'] = $storeName ?? $this->getName();
        $options['driver'] = $driver;
        $this->options['store'] = $options;
        return $this;
    }

    /**
     * @param \Sayla\Objects\Transformers\TransformerFactory $factory
     * @return $this
     */
    public function transformerFactory(TransformerFactory $factory)
    {
        $this->options[__FUNCTION__] = $factory;
        return $this;
    }

    private function getOptionResolver()
    {
        if (!isset($this->optionsResolver)) {
            $resolver = new OptionsResolver();
            $resolver->setRequired(['objectClass', 'attributes', 'resolveOnRequest', 'propertyTypeOptions']);

            $resolver->setAllowedTypes('objectClass', 'string');
            $resolver->setAllowedTypes('propertyTypeOptions', 'array');
            $resolver->setAllowedTypes('attributes', 'array');
            $resolver->setAllowedTypes('resolveOnRequest', 'array');
            $resolver->setDefault('resolveOnRequest', function (Options $options) {
                /** @var ResponsableObject $objectClass */
                $objectClass = $options['objectClass'];
                if (is_subclass_of($objectClass, ResponsableObject::class)) {
                    return $objectClass::getResponseResolvableAttributes();
                }
                return [];
            });

            $resolver->setDefined('dispatcher');
            $resolver->setAllowedTypes('dispatcher', Dispatcher::class);

            $resolver->setDefined('store');
            $resolver->setAllowedTypes('store', 'array');

            $resolver->setDefault('extends', null);
            $resolver->setAllowedTypes('extends', ['string', 'null']);

            $resolver->setDefault('classFile', null);
            $resolver->setAllowedTypes('classFile', ['string', 'null']);

            $resolver->setDefault('definitionFile', null);
            $resolver->setAllowedTypes('definitionFile', ['string', 'null']);

            $resolver->setDefaults(['traits' => []]);
            $resolver->setAllowedTypes('traits', 'array');

            $resolver->setDefaults(['interfaces' => []]);
            $resolver->setAllowedTypes('interfaces', 'array');

            $resolver->setDefault('name', function (Options $options) {
                return $options['objectClass'];
            });
            $resolver->setAllowedTypes('name', 'string');

            $resolver->setDefault('alias', function (Options $options) {
                return self::makeAlias(class_basename($options['objectClass']));
            });
            $resolver->setNormalizer('alias', function (Options $options, $value) {
                return str_replace('\\', '', $value);
            });
            $resolver->setAllowedTypes('alias', 'string');
            $this->optionsResolver = $resolver;
        }
        return $this->optionsResolver;
    }
}