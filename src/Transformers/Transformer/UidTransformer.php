<?php namespace Sayla\Objects\Transformers\Transformer;

use Ramsey\Uuid\Uuid;
use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class UidTransformer implements ValueTransformer
{
    use ValueTransformerTrait;

    /**
     * @param mixed $value
     * @return string|null
     */
    public function build($value)
    {
        if (empty($value) && $this->options->get('autoBuild', false)) {
            return (string)Uuid::uuid4();
        }
        return $value;
    }

    public function getScalarType(): ?string
    {
        return 'string';
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function smash($value)
    {
        $autoSmash = $this->options['autoSmash'] ?? true;
        if (empty($value) && $autoSmash) {
            return (string)Uuid::uuid4();
        }
        return $value;
    }
}