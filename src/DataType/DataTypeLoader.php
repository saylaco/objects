<?php

namespace Sayla\Objects\DataType;

use Sayla\Objects\Builder\DataTypeConfig;
use Symfony\Component\Yaml\Yaml;

class DataTypeLoader
{
    const CONFIG_ANNOTATIONS = 'annotations';
    const CONFIG_ARRAY = 'array';
    protected $callbacks = [
        self::CONFIG_ANNOTATIONS => [],
        self::CONFIG_ARRAY => []
    ];
    protected $objects = [];
    /** @var callable */
    protected $onDiscover;

    private $locations = [];

    public function addAnnotationConfigCallback(callable $callable)
    {
        $this->callbacks[self::CONFIG_ANNOTATIONS][] = $callable;
        return $this;
    }

    public function addArrayConfigCallback(callable $callable)
    {
        $this->callbacks[self::CONFIG_ARRAY][] = $callable;
        return $this;
    }

    /**
     * @param string $directory
     * @param string $namespace
     * @return $this
     */
    public function addLocation(string $directory, string $namespace)
    {
        $this->locations[] = compact('directory', 'namespace');
        return $this;
    }

    /**
     * @param \Sayla\Objects\DataType\DataTypeManager $manager
     * @return DataTypeConfig[]
     */
    public function build(DataTypeManager $manager)
    {
        if (filled($this->locations)) {
            $this->discoverObjects();
        }
        $builders = [];
        foreach ($this->objects as $object) {
            $builders[] = $this->makeBuilder($manager, $object);
        }
        return $builders;
    }

    /**
     * @param string $directory
     * @param string $namespace
     */
    protected function discoverAnnotatedTypes(string $directory, string $namespace): void
    {
        $configType = self::CONFIG_ANNOTATIONS;
        foreach (glob($directory . '/*.php') as $file) {
            $reader = fopen($file, 'r');
            $isDataType = false;
            $docBlockLines = [];
            while (!feof($reader)) {
                $line = (string)fgets($reader);
                $isDocBlock = preg_match('/\/\*\*/', $line, $match);
                if ($isDocBlock) {
                    $docBlockLines[] = $line;
                    while (!feof($reader)) {
                        $line = (string)fgets($reader);
                        $docBlockLines[] = $line;
                        $isDataType = preg_match('/\*\s*@DataType\(.*\)/', $line, $match);
                        if ($isDataType) {
                            break;
                        }
                    }
                    break;
                }

                $isClassDec = preg_match('/\\s*class\\s+\\w+\\s+/ui', $line, $match);
                if ($isClassDec) break;
            }
            fclose($reader);
            if (!$isDataType) continue;
            $name = str_before(basename($file), '.');
            $class = str_finish($namespace, '\\') . $name;
            $this->objects[] = $obj = compact('class', 'file', 'configType');
            $this->runDiscoverCallback($obj);
        }
    }

    /**
     * @param string $directory
     * @param string $namespace
     * @return array
     */
    public function discoverObjects()
    {
        foreach ($this->locations as $i => $location) {
            $this->discoverYamlTypes($location['directory'], $location['namespace']);
            $this->discoverAnnotatedTypes($location['directory'], $location['namespace']);
            unset($this->locations[$i]);
        }
        return $this->objects;
    }


    /**
     * @param string $directory
     * @param string $namespace
     */
    protected function discoverYamlTypes(string $directory, string $namespace): void
    {
        $configType = self::CONFIG_ARRAY;
        foreach (glob($directory . '/*.yml') as $file) {
            $name = str_before(basename($file), '.');
            $class = str_finish($namespace, '\\') . $name;
            $config = Yaml::parseFile($file);
            $this->objects[] = $obj = compact('class', 'file', 'configType', 'config');
            $this->runDiscoverCallback($obj);
        }
    }


    protected function makeBuilder(DataTypeManager $manager, array $object): DataTypeConfig
    {
        $configType = $object['configType'];
        $object = $this->runCallbacks($configType, $object);
        switch ($configType) {
            case self::CONFIG_ARRAY:
                return $manager->addConfigured(array_merge($object['config'], [
                    'objectClass' => $object['class'],
                    'name' => $object['config']['name'] ?? $object['class']
                ]))->enableOptionsValidation();
            case self::CONFIG_ANNOTATIONS:
            default:
                return $manager->addClass($object['class'], $object['file']);
        }
    }

    /**
     * @param string $configType
     * @param array $object
     * @return array
     */
    protected function runCallbacks(string $configType, array $object): array
    {
        foreach ($this->callbacks[$configType] as $callback) {
            $object['config'] = call_user_func($callback, $object['class'], $object['config']);
        }
        return $object;
    }

    /**
     * @param callable $onDiscover
     */
    public function setOnDiscover(callable $onDiscover): void
    {
        $this->onDiscover = $onDiscover;
    }

    private function runDiscoverCallback(array $obj)
    {
        if ($this->onDiscover) {
            call_user_func($this->onDiscover, $obj);
        }
    }

}