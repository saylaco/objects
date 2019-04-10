<?php namespace Sayla\Objects\Transformers\Transformer;

use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class DatabaseKeyTransformer implements ValueTransformer
{
    use ValueTransformerTrait;

    /**
     * @param mixed $value
     * @return string|null
     */
    public function build($value)
    {
        return is_null($value) ? null : $this->getTypedValue($value, $this->options->get('varType'));
    }

    public function getScalarType(): ?string
    {
        return $this->options->get('varType', 'int');
    }

    /**
     * @param $value
     * @param $varType
     * @return int|string
     */
    public function getTypedValue($value, $varType)
    {
        if ($varType == 'string') {
            return strval($value);
        }
        return intval($value);
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function smash($value)
    {
        return is_null($value) ? null : $this->getTypedValue($value, $this->options->get('varType'));
    }
}