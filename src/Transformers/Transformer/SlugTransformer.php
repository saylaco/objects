<?php namespace Sayla\Objects\Transformers\Transformer;

use Illuminate\Support\Str;
use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class SlugTransformer implements ValueTransformer
{
    use ValueTransformerTrait;

    /**
     * @param mixed $value
     * @return mixed
     */
    public function build($value)
    {
        $separator = $this->options->get('separator', '-');
        return $this->makeSlug($value, $separator);
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
        $separator = $this->options->get('separator', '-');
        return $this->makeSlug($value, $separator);
    }

    /**
     * @param $value
     * @param $separator
     * @return null|string
     */
    protected function makeSlug($value, $separator)
    {
        return empty($value) ? null : Str::slug($value, $separator);
    }
}