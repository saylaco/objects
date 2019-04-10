<?php

namespace Sayla\Objects\Builder;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use Sayla\Exception\Error;
use Sayla\Objects\Attribute\PropertyType\TransformationDescriptorMixin;
use Sayla\Objects\Contract\IDataObject;
use Sayla\Objects\Contract\StorableObject;
use Sayla\Objects\DataType\DataType;
use Sayla\Objects\DataType\DataTypeDescriptor;
use Sayla\Objects\StorableTrait;
use Sayla\Objects\Transformers\AttributeValueTransformer;

class ClassWriter
{
    protected $classNamespace;
    protected $codeOutputDirectory;
    private $descriptorClassSuffix = 'Descriptor';
    private $interfaceSuffix = 'DT';
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
        $descriptorClass = $this->writeDescriptorClass($dataType->getDescriptor());
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
        $transformerMixin = $dataType->getDescriptor()->getMixin(TransformationDescriptorMixin::class);
        $transformer = $transformerMixin->getTransformer();
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
            $class->addComment('@property ' . $varType . ' ' . $attributeName);
        }
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
            if (isset($class->methods[$mixin['name']])) {
                $annotatedMixins[] = $mixin['class'];
                continue;
            }

            $args = [];
            $method = $class->addMethod($mixin['name']);
            foreach ($mixin['parameters'] as $parameter) {
                $param = $method->addParameter($parameter['name'])->setTypeHint($parameter['type']);
                if ($parameter['optional']) {
                    $param->setDefaultValue(null);
                }
                $args[] = '$' . $parameter['name'];
            }
            $delegationCallCode = '$mixin->' . $mixin['methodName'] . '(' . join(', ', $args) . ');';
            $lines = [
                '/**  @var \\' . $mixin['class'] . ' $mixin */',
                '$mixin = $this->mixins[' . var_str($mixin['mixinName']) . '];',
            ];
            if (
                $mixin['returnType'] !== 'void'
                && (
                    ($mixin['returnType'] && !str_is('set[A-Z]', $mixin['name']))
                    || !str_is(['is[A-Z]', 'get[A-Z]'], $mixin['name'])
                )
            ) {
                $lines[] = 'return ' . $delegationCallCode;
                $method->setReturnType($mixin['returnType']);
            } else {

                $lines[] = $delegationCallCode;
                $lines[] = 'return $this;';
                $method->setReturnType('self');
            }
            $method->setBody(
                join(PHP_EOL, $lines)
            );
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
        foreach ($dataType->getInterfaces() as $interfaceClass)
            $interface->addExtend(qualify_var_type($interfaceClass));

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
        foreach ($dataType->getTraits() as $traitClass)
            $trait->addTrait(qualify_var_type($traitClass));

        if ($dataType->hasStore()) {
            $trait->addTrait(qualify_var_type(StorableTrait::class));
        }


        if ($dataType->getDescriptor()->hasMixin(TransformationDescriptorMixin::class)) {
            $this->addAttributeAnnotations($dataType, $trait);
            $this->addAttributeAnnotations($dataType, $interface);
        }
        return $classes;
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