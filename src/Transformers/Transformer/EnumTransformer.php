<?php namespace Sayla\Objects\Transformers\Transformer;

use BenSampo\Enum\Enum;
use Sayla\Objects\Contract\DataObject\SupportsDataTypeManager;
use Sayla\Objects\Contract\DataObject\SupportsDataTypeManagerTrait;
use Sayla\Objects\Transformers\AttributeValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class EnumTransformer implements AttributeValueTransformer, SupportsDataTypeManager
{
    use SupportsDataTypeManagerTrait;
    use ValueTransformerTrait;

    public function build($value): ?Enum
    {
        if ($value instanceof Enum) {
            return $value;
        }
        if (isset($value['value'])) {
            $value = $value['value'];
        } else if (is_object($value) && $value->value) {
            $value = $value->value;
        }

        if ($this->getEnumClass()::hasKey($value)) {
            return $this->getEnumClass()::getInstance($this->getEnumClass()::getValue($value));
        }
        return $this->getEnumClass()::getInstance($value);
    }

    public function getScalarType(): ?string
    {
        return 'mixed';
    }

    public function getVarType(): string
    {
        return qualify_var_type($this->getEnumClass());
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function smash($value)
    {
        if ($value instanceof Enum) {
            return $value->value;
        }
        return $value;
    }

    /**
     * @return Enum|string
     */
    private function getEnumClass()
    {
        return $this->options->class;
    }
}