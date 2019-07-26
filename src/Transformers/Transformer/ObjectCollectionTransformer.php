<?php namespace Sayla\Objects\Transformers\Transformer;

use ReflectionClass;
use Sayla\Objects\Contract\DataObject\SupportsDataTypeManager;
use Sayla\Objects\Contract\DataObject\SupportsDataTypeManagerTrait;
use Sayla\Objects\DataObject;
use Sayla\Objects\ObjectCollection;
use Sayla\Objects\Transformers\AttributeValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;
use Sayla\Util\JsonHelper;

class ObjectCollectionTransformer implements AttributeValueTransformer, SupportsDataTypeManager
{
    use SupportsDataTypeManagerTrait;
    use ValueTransformerTrait;

    /**
     * @param mixed $value
     * @return \Sayla\Objects\ObjectCollection
     */
    public function build($value): ObjectCollection
    {
        $collection = $this->newCollectionInstance();
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        if (!is_null($value)) {
            $collection->makeObjects($value);
        }
        return $collection;
    }

    /**
     * @return mixed|null
     */
    protected function getDataType()
    {
        return $this->options->dataType ?? $this->options->item;
    }

    public function getScalarType(): ?string
    {
        return 'string';
    }

    public function getVarType(): string
    {
        return qualify_var_type($this->options->class ?: ObjectCollection::class) . '|' . $this->getDataType() . '[]';
    }

    /**
     * @return mixed|object|\Sayla\Objects\ObjectCollection|static
     */
    protected function newCollectionInstance(): ObjectCollection
    {
        if ($this->options->resolver != null) {
            return call_user_func($this->options->resolver);
        } elseif ($this->options->class != null) {
            return (new ReflectionClass($this->options->class))->newInstance();
        } else {
            /** @var DataObject|string $dataType */
            $dataType = $this->getDataType();
            if (is_subclass_of($dataType, DataObject::class)) {
                return $dataType::newCollection();
            }
            return $this->options->get('restricted', true)
                ? self::getDataTypeManager()->get($dataType)->newCollection()->toUnrestrictedCollection()
                : self::getDataTypeManager()->get($dataType)->newCollection();
        }
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function smash($value)
    {
        if ($value instanceof ObjectCollection) {
            return $value->values()->toJson();
        }
        return JsonHelper::encode($value);
    }
}