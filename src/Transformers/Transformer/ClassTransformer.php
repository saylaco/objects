<?php

namespace Sayla\Objects\Transformers\Transformer;

use Sayla\Exception\ClassNotFoundException;
use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class ClassTransformer implements ValueTransformer
{
    use ValueTransformerTrait;

    /**
     * @param mixed $value
     * @return string|null
     */
    public function build($value)
    {
        $this->verifyClassExists($value);
        return is_null($value) ? null : (string)$value;
    }

    public function getScalarType(): string
    {
        return 'string';
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function smash($value)
    {
        $this->verifyClassExists($value);
        return is_null($value) ? null : (string)$value;
    }

    /**
     * @param $value
     * @throws \Sayla\Exception\ClassNotFoundException
     */
    protected function verifyClassExists($value): void
    {
        if (!class_exists($value)) {
            throw new ClassNotFoundException($value);
        }
    }
}