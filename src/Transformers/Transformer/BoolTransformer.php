<?php namespace Sayla\Objects\Transformers\Transformer;

use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class BoolTransformer implements ValueTransformer
{
    use ValueTransformerTrait;

    /**
     * @param mixed $value
     * @return string|null
     */
    public function build($value)
    {
        if (is_null($value)) {
            if (isset($this->options->default)) {
                return $this->options->default;
            }
            return null;
        }
        return boolval($value);
    }

    public function getScalarType(): ?string
    {
        return 'boolean';
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function smash($value)
    {
        if (is_null($value)) {
            if ($this->options->get('force', false)) {
                return false;
            }
            if (isset($this->options->default)) {
                return $this->options->default;
            }
            return null;
        }
        return boolval($value);
    }
}