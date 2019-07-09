<?php namespace Sayla\Objects\Transformers\Transformer;

use Sayla\Objects\Transformers\AttributeValueTransformer;
use Sayla\Objects\Transformers\SmashesToHashMap;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class JsonObjectTransformer extends JsonTransformer implements AttributeValueTransformer, SmashesToHashMap
{
    use ValueTransformerTrait;

    public function getVarType(): string
    {
        return parent::getVarType() . '|' . $this->getClassName();
    }
}