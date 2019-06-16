<?php

namespace Sayla\Objects\Attribute\Resolver;

use Sayla\Objects\Contract\Attributes\AttributeResolver;
use Sayla\Objects\Contract\Attributes\AttributeResolverTrait;
use Sayla\Objects\DataObject;
use Sayla\Util\Evaluator;

class AliasResolver implements AttributeResolver
{
    use AttributeResolverTrait;
    /** @var string */
    protected $dependsOn;
    /** @var string */
    protected $expression;

    /**
     * AliasResolver constructor.
     * @param string $dependsOn
     * @param string $expression
     */
    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }


    /**
     * @param \Sayla\Objects\DataObject $owningObject
     * @return mixed
     */
    public function resolve(DataObject $owningObject)
    {
        return Evaluator::toEval('$object->' . $this->expression, ['object' => $owningObject]);
    }

    public function resolveMany($objects): array
    {
        return $this->resolveManyUsingSingleResolver($objects);
    }

    /**
     * @param string $dependsOn
     * @return $this
     */
    public function setDependsOn(string $dependsOn)
    {
        $this->dependsOn = $dependsOn;
        return $this;
    }
}