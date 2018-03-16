<?php

namespace Sayla\Objects\Resolvers;

use Sayla\Objects\Contract\AttributeResolver;
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
    public function __construct(string $expression, string $dependsOn = null)
    {
        $this->dependsOn = $dependsOn;
        $this->expression = $expression;
    }


    /**
     * @param \Sayla\Objects\DataObject $owningObject
     * @return mixed
     */
    public function resolve(DataObject $owningObject)
    {
        if (isset($this->dependsOn) && !isset($owningObject[$this->dependsOn])) {
            $owningObject[$this->dependsOn];
        }
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