<?php

namespace Sayla\Objects\Builder;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use ReflectionClass;
use Sayla\Objects\Attribute\PropertyType\TransformationDescriptorMixin;
use Sayla\Objects\Contract\DataObject\StorableObject;
use Sayla\Objects\Contract\DataObject\StorableObjectTrait;
use Sayla\Objects\Contract\IDataObject;
use Sayla\Objects\Contract\Stores\ModifiesObjectBehavior;
use Sayla\Objects\DataType\DataType;
use Sayla\Objects\DataType\DataTypeDescriptor;
use Sayla\Objects\Stores\StoreManager;

class ClassWriter
{
    private static $reflectionClasses = [];
    private $descriptorClassSuffix = 'DTDescriptor';
    private $interfaceSuffix = 'DT';
    /** @var \Sayla\Objects\Stores\StoreManager */
    private $storeManager;
    private $traitSuffix = 'DTrait';

    public function __invoke(DataType $dataType)
    {
        $descriptor = $dataType->getDescriptor();
        $baseName = class_basename($dataType->getObjectClass());
        $namespace = class_parent_namespace($dataType->getObjectClass());
        $ns = $this->getPhpNamespace($namespace);
        $descriptorClass = $this->writeDescriptorClass($descriptor, $ns, $baseName);
        $classes = $this->writeObjectClass($dataType, $ns, $baseName, $descriptorClass->getName());
        $classes['desc'] = $descriptorClass;
        $this->createFile($dataType->getDefinitionFile(), $ns, $classes['dt'], $classes['trait'], $classes['desc']);
    }

    /**
     * @param \Sayla\Objects\DataType\DataType $dataType
     * @param \Nette\PhpGenerator\ClassType $class
     * @throws \ErrorException
     * @throws \Sayla\Exception\Error
     */
    protected function addAttributeAnnotations(DataType $dataType, ClassType $class): void
    {
        /** @var \Sayla\Objects\Attribute\PropertyType\TransformationDescriptorMixin $transformerMixin */
        $descriptor = $dataType->getDescriptor();
        foreach ($descriptor->getVarTypes() as $attributeName => $varTypes) {
            $varTypeStr = join('|', $varTypes);
            if (!$descriptor->isWritable($attributeName) && $descriptor->isReadable($attributeName)) {
                $class->addComment('@property-read ' . $varTypeStr . ' ' . $attributeName);
            } else if ($descriptor->isWritable($attributeName) && !$descriptor->isReadable($attributeName)) {
                $class->addComment('@property-write ' . $varTypeStr . ' ' . $attributeName);
            } else {
                $class->addComment('@property ' . $varTypeStr . ' ' . $attributeName);
            }
        }
    }

    protected function createFile(string $fileName, PhpNamespace $namespace, ClassType ...$classes): void
    {
        $printer = new PsrPrinter();
        $code = '<?php ' . PHP_EOL
            . $printer->printNamespace($namespace) . PHP_EOL;
        foreach ($classes as $class) {
            $code .= $printer->printClass($class) . PHP_EOL;
        }
        file_put_contents($fileName, $code);
    }

    /**
     * @param string $namespace
     * @return \Nette\PhpGenerator\PhpNamespace|string
     */
    protected function getPhpNamespace(string $namespace)
    {
        $namespace = new PhpNamespace($namespace);
        return $namespace;
    }

    /**
     * @return \Sayla\Objects\Stores\StoreManager
     */
    protected function getStoreManager(): StoreManager
    {
        return $this->storeManager ?? StoreManager::resolve();
    }

    /**
     * @param \Sayla\Objects\Stores\StoreManager $storeManager
     * @return ClassWriter
     */
    public function setStoreManager(StoreManager $storeManager): ClassWriter
    {
        $this->storeManager = $storeManager;
        return $this;
    }

    /**
     * @param string $descriptorClassSuffix
     * @return ClassWriter
     */
    public function setDescriptorClassSuffix(string $descriptorClassSuffix): ClassWriter
    {
        $this->descriptorClassSuffix = $descriptorClassSuffix;
        return $this;
    }

    /**
     * @param string $interfaceSuffix
     * @return ClassWriter
     */
    public function setInterfaceSuffix(string $interfaceSuffix): ClassWriter
    {
        $this->interfaceSuffix = $interfaceSuffix;
        return $this;
    }

    /**
     * @param string $traitSuffix
     * @return ClassWriter
     */
    public function setTraitSuffix(string $traitSuffix): ClassWriter
    {
        $this->traitSuffix = $traitSuffix;
        return $this;
    }

    protected function writeDescriptorClass(DataTypeDescriptor $descriptor, PhpNamespace $namespace,
                                            string $className): ClassType
    {
        $class = new ClassType($className . $this->descriptorClassSuffix, $namespace);
        $class->setFinal(true)->setExtends(qualify_var_type(DataTypeDescriptor::class));
        $annotatedMixins = [];
        foreach ($descriptor->getMixins()->getMixinMethods() as $mixin) {
            $annotatedMixins[] = $mixin['class'];
        }
        foreach (array_unique($annotatedMixins) as $mixinClass)
            $class->addComment('@mixin \\' . $mixinClass);
        return $class;
    }

    /**
     * @param \Sayla\Objects\DataType\DataType $dataType
     * @param \Nette\PhpGenerator\PhpNamespace $namespace
     * @param string $className
     * @param string $descriptorClass
     * @return ClassType[]
     * @throws \ErrorException
     * @throws \ReflectionException
     * @throws \Sayla\Exception\Error
     */
    protected function writeObjectClass(DataType $dataType, PhpNamespace $namespace, string $className,
                                        string $descriptorClass): array
    {
        $classes = [];
        $classes['dt'] = $interface = new ClassType(
            $className . $this->interfaceSuffix,
            $namespace
        );
        $interface->setType(ClassType::TYPE_INTERFACE);

        if ($dataType->hasStore()) {
            $interface->addExtend(qualify_var_type(StorableObject::class));
        } else {
            $interface->addExtend(qualify_var_type(IDataObject::class));
        }

        $classes['trait'] = $trait = new ClassType(
            $className . $this->traitSuffix,
            $namespace
        );
        $trait->setType(ClassType::TYPE_TRAIT);
        $trait->addMethod('dataTypeName')
            ->setReturnType('string')
            ->setStatic(true)
            ->setBody('return ' . var_str($dataType->getName()) . ';')
            ->setVisibility(ClassType::VISIBILITY_PUBLIC);
        $trait->addComment("@method static {$descriptorClass} descriptor()");

        $traits = $dataType->getTraits();
        $interfaces = $dataType->getInterfaces();

        if ($dataType->hasStore()) {
            $traits[] = StorableObjectTrait::class;
        }


        if (filled($storeDriver = $dataType->getStoreDriver())) {
            /** @var string|ModifiesObjectBehavior $storeClass */
            $storeClass = $this->getStoreManager()->getDriverClass($storeDriver);

            if (is_subclass_of($storeClass, ModifiesObjectBehavior::class)) {
                foreach ($storeClass::getObjectBehavior() as $behavior) {
                    $behaviorClass = $this->getReflection($behavior);
                    if ($behaviorClass->isTrait()) {
                        $traits[] = $behaviorClass->getName();
                    } elseif ($behaviorClass->isInterface()) {
                        $interfaces[] = $behaviorClass->getName();
                    }
                }
            }
        }

        foreach (array_unique($interfaces) as $interfaceClass) {
            $interface->addExtend(qualify_var_type($interfaceClass));
        }

        foreach (array_unique($traits) as $traitClass)
            $trait->addTrait(qualify_var_type($traitClass));

        if ($dataType->getDescriptor()->hasMixin(TransformationDescriptorMixin::class)) {
            $this->addAttributeAnnotations($dataType, $trait);
            $this->addAttributeAnnotations($dataType, $interface);
            $attributeNames = $dataType->getAttributes()->getNames();
            sort($attributeNames);
            $interface->addConstant('Attributes', $attributeNames);
            foreach ($attributeNames as $name)
                $interface->addConstant(ucfirst($name), $name);
        }
        return $classes;
    }

    /**
     * @param $class
     * @return \ReflectionClass
     * @throws \ReflectionException
     */
    private function getReflection($class): ReflectionClass
    {
        if (!is_string($class)) {
            $class = get_class($class);
        }
        return self::$reflectionClasses[$class] ?? self::$reflectionClasses[$class] = new ReflectionClass($class);
    }

}