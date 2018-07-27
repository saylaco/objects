<?php namespace Sayla\Objects\Transformers\Transformer;

use Sayla\Objects\ObjectCollection;
use Sayla\Util\JsonHelper;

class ObjectArrayTransformer extends ObjectCollectionTransformer
{

    /**
     * @param mixed $value
     * @return array
     */
    public function smash($value)
    {
        if ($value instanceof ObjectCollection) {
            return $value->jsonSerialize();
        }
        return JsonHelper::encodeDecodeToArray($value);
    }

}