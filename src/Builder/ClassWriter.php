<?php

namespace Sayla\Objects\Builder;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use ReflectionClass;
use Sayla\Exception\Error;
use Sayla\Objects\Attribute\PropertyType\TransformationDescriptorMixin;
use Sayla\Objects\Contract\Attributes\AssociationResolver;
use Sayla\Objects\Contract\DataObject\StorableObject;
use Sayla\Objects\Contract\DataObject\StorableObjectTrait;
use Sayla\Objects\Contract\IDataObject;
use Sayla\Objects\Contract\Stores\ModifiesObjectBehavior;
use Sayla\Objects\DataType\DataType;
use Sayla\Objects\DataType\DataTypeDescriptor;
use Sayla\Objects\DataType\DataTypeManager;
use Sayla\Objects\Stores\StoreManager;
use Sayla\Objects\Transformers\AttributeValueTransformer;
use Throwable;

class ClassWriter
{
    private static $reflectionClasses = [];
    protected $classNamespace;
    protected $codeOutputDirectory;
    private $descriptorClassSuffix = 'Descriptor';
    private $interfaceSuffix = 'DT';
    /** @var \Sayla\Objects\Stores\StoreManager */
    private $storeManager;
    private $traitSuffix = 'Trait';

    /**
     * ClassWriter constructor.
     * @param $classNamespace
     * @param $codeOutputDirectory
     */
    public function __construct(string $classNamespace, string $codeOutputDirectory)
    {
        $this->classNamespace = new PhpNamespace($classNamespace);
        $this->codeOutputDirectory = $codeOutputDirectory;
    }

    public function __invoke(DataType $dataType)
    {
        $descriptor = $dataType->getDescriptor();
        $descriptorClass = $this->writeDescriptorClass($descriptor);
        $classes = $this->writeObjectClass($dataType, $descriptorClass->getName());
        $classes['desc'] = $descriptorClass;
        $this->createFile($classes['dt']);
        $this->createFile($classes['trait'], $classes['desc']);
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
        $transformerMixin = $descriptor->getMixin(TransformationDescriptorMixin::class);
        $transformer = $transformerMixin->getTransformer();
        $varTypes = [];

        foreach ($descriptor->getResolvable() as $attributeName) {
            $resolver = $descriptor->getResolver($attributeName);
            if ($resolver instanceof AssociationResolver) {
                try {
                    $varType = qualify_var_type(DataTypeManager::resolve()
                        ->getDescriptor($resolver->getAssociatedDataType())
                        ->getObjectClass());
                } catch (Throwable $throwable) {
                    $varType = qualify_var_type($resolver->getAssociatedDataType());
                }

                if (!$resolver->isSingular()) {
                    $varType .= '[]';
                }

                $varTypes[$attributeName] = $varType;
            }
        }

        foreach ($descriptor->getResolvable() as $attributeName) {
            $resolver = $descriptor->getResolver($attributeName);
            if ($resolver instanceof AssociationResolver) {
                try {
                    $varType = qualify_var_type(DataTypeManager::resolve()
                        ->getDescriptor($resolver->getAssociatedDataType())
                        ->getObjectClass());
                } catch (Throwable $throwable) {
                    $varType = qualify_var_type($resolver->getAssociatedDataType());
                }

                if (!$resolver->isSingular()) {
                    $varType .= '[]';
                }
                $varTypes[$attributeName] = $varType;
            }
        }

        foreach (array_sort($transformer->getAttributeNames()) as $attributeName) {
            try {
                $valueTransformer = $transformer->getValueTransformer($attributeName);
                $varType =
                    $valueTransformer instanceof AttributeValueTransformer
                        ? qualify_var_type($valueTransformer->getVarType())
                        : $valueTransformer->getScalarType() ?: ($dataType
                        ->getAttributes()
                        ->getAttribute($attributeName)
                        ->getTypeHandle());
            } catch (Error $exception) {
                $varType = $transformer->getAttributeOptions()[$attributeName]['type'];
            }
            $varTypes[$attributeName] = $varType;
        }
        foreach ($varTypes as $attributeName => $varType)
            $class->addComment('@property ' . $varType . ' ' . $attributeName);
    }

    protected function createFile(ClassType ...$classes): void
    {
        $printer = new PsrPrinter();
        $code = '<?php ' . PHP_EOL
            . $printer->printNamespace($this->classNamespace) . PHP_EOL;
        foreach ($classes as $class) {
            $code .= $printer->printClass($class) . PHP_EOL;
        }
        file_put_contents("{$this->codeOutputDirectory}/{$classes[0]->getName()}.php", $code);
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

    protected function writeDescriptorClass(DataTypeDescriptor $descriptor): ClassType
    {
        $className = $this->normalizeClassName($descriptor->getObjectClass(), $this->descriptorClassSuffix);
        $class = new ClassType($className, $this->classNamespace);
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
     * @param string $descriptorClass
     * @return ClassType[]
     * @throws \ErrorException
     * @throws \Sayla\Exception\Error
     */
    protected function writeObjectClass(DataType $dataType, string $descriptorClass): array
    {
        $classes = [];
        $classes['dt'] = $interface = new ClassType(
            $this->normalizeClassName($dataType->getName(), $this->interfaceSuffix),
            $this->classNamespace
        );
        $interface->setType(ClassType::TYPE_INTERFACE);

        if ($dataType->hasStore()) {
            $interface->addExtend(qualify_var_type(StorableObject::class));
        } else {
            $interface->addExtend(qualify_var_type(IDataObject::class));
        }

        $classes['trait'] = $trait = new ClassType(
            $this->normalizeClassName($dataType->getName(), $this->traitSuffix),
            $this->classNamespace
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

    /**
     * @param string $possibleClassName
     * @param string $suffix
     * @return string
     */
    private function normalizeClassName(string $possibleClassName, string $suffix): string
    {
        $_className = $possibleClassName . $suffix;
        $className = class_basename($_className) ?? $_className;
        return $className;
    }
}