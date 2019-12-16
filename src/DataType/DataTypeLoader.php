<?php

namespace Sayla\Objects\DataType;

use Illuminate\Support\Str;
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

    protected function addDataTypeConfig(DataTypeManager $manager, array $object): DataTypeConfig
    {
        $configType = $object['configType'];
        $object = $this->runCallbacks($configType, $object);
        $cachedConfig = array_only($object, ['definitionFile', 'alias', 'name']);
        switch ($configType) {
            case self::CONFIG_ARRAY:
                $cachedConfig = array_merge($cachedConfig, $object['config'], [
                    'objectClass' => $object['class'],
                    'name' => $object['config']['name'] ?? $object['class']
                ]);
                return $manager->addConfigured($cachedConfig)->enableOptionsValidation();
            case self::CONFIG_ANNOTATIONS:
            default:
                return $manager->addClass($object['class'], $object['file'], $cachedConfig);
        }
    }

    /**
     * @param string $directory
     * @param string $namespace
     * @return $this
     */
    public function addLocation(string $directory, string $namespace, string $definitionsDir,
                                string $aliasPrefix = null)
    {
        $addChildren = Str::endsWith($directory, '*');
        if ($addChildren) {
            $directory = rtrim($directory, '/*');
        }
        $this->locations[] = compact('directory', 'namespace', 'definitionsDir', 'aliasPrefix');

        if ($addChildren) {
            foreach ($this->getChildLocations($directory, $namespace) as $details) {
                $details['rootNamespace'] = $namespace;
                $details['definitionsDir'] = $definitionsDir;
                $this->locations[] = $details;
            }
        }
        return $this;
    }

    /**
     * @param \Sayla\Objects\DataType\DataTypeManager $manager
     * @return DataTypeConfig[]
     */
    public function configure(DataTypeManager $manager)
    {
        if (filled($this->locations)) {
            $this->discoverObjects();
        }
        $configs = [];
        foreach ($this->objects as $object) {
            $configs[] = $this->addDataTypeConfig($manager, $object);
        }
        return $configs;
    }

    /**
     * @param string $directory
     * @param string $namespace
     */
    protected function discoverAnnotatedTypes(array $location): void
    {
        $configType = self::CONFIG_ANNOTATIONS;
        foreach (glob($location['directory'] . '/*.php') as $file) {
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
            $class = str_finish($location['namespace'], '\\') . $name;
            $this->objects[] = $this->runDiscoverCallback(
                $location, $name, compact('class', 'file', 'configType', 'definitionFile')
            );
        }
    }


    public function discoverObjects()
    {
        foreach ($this->locations as $i => $location) {
            $this->discoverYamlTypes($location);
            $this->discoverAnnotatedTypes($location);
            unset($this->locations[$i]);
        }
        return $this->objects;
    }

    protected function discoverYamlTypes(array $location): void
    {
        $configType = self::CONFIG_ARRAY;
        foreach (glob($location['directory'] . '/*.yml') as $file) {
            $name = str_before(basename($file), '.');
            $class = str_finish($location['namespace'], '\\') . $name;
            $config = Yaml::parseFile($file);
            $this->objects[] = $this->runDiscoverCallback($location, $name, compact(
                'class',
                'file',
                'configType',
                'config',
                'definitionFile'
            ));
        }
    }

    protected function getChildLocations(string $directory, string $namespace)
    {
        $locations = [];
        foreach (glob($directory . '/*', GLOB_ONLYDIR) as $_directory) {
            $_namespace = $namespace . '\\' . basename($_directory);
            $locations[] = ['directory' => $_directory, 'namespace' => $_namespace];
            $locations = array_merge($locations, $this->getChildLocations($_directory, $_namespace));
        }
        return $locations;
    }

    /**
     * @param array $location
     * @param string $name
     * @return string
     */
    protected function getDefinitionFile(array $location, string $name): string
    {
        if (isset($location['rootNamespace'])) {
            $fullName = trim(Str::after($location['namespace'], $location['rootNamespace']), '\\') . $name;
        } else {
            $fullName = $name;
        }
        $definitionFile = $location['definitionsDir'] . '/' . Str::studly(str_replace('\\', '-', $fullName)) . 'DT.php';
        return $definitionFile;
    }

    /**
     * @return array[]
     */
    public function getDiscoveredObjects()
    {
        if (filled($this->locations)) {
            $this->discoverObjects();
        }
        return $this->objects;
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

    private function runDiscoverCallback(array $location, string $name, array $obj)
    {
        $obj['name'] = $obj['class'];
        $obj['definitionFile'] = $this->getDefinitionFile($location, $name);
        $obj['alias'] = $location['aliasPrefix'] . $name;
        if ($this->onDiscover) {
            call_user_func($this->onDiscover, $obj);
        }
        return $obj;
    }

}