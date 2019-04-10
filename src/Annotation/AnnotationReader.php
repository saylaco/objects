<?php
/**
 * =============================================================================
 * Copyright (c) 2013, Philip Graham
 * All rights reserved.
 *
 * This file is part of Reed and is licensed by the Copyright holder under
 * the 3-clause BSD License.    The full text of the license can be found in the
 * LICENSE.txt file included in the root directory of this distribution or at
 * the link below.
 * =============================================================================
 *
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace Sayla\Objects\Annotation;

use InvalidArgumentException;
use ReflectionClass;
use Reflector;
use zpt\anno\ReflectorNotCommentedException;

/**
 * This class parses a given Reflector for annotations and provides array style
 * or method style access to them.    Only Reflectors that implement the
 * getDocComment method are supported.
 *
 * @author Philip Graham <philip@zeptech.ca>
 */
class AnnotationReader
{
    /** @var bool|string */
    private $docComment;
    /** @var \Sayla\Objects\Annotation\AnnotationParser */
    private $parser;
    /** @var \Sayla\Objects\Annotation\ParserResult */
    private $parserResult;

    /**
     * Create a new Annotations instance.
     *
     * @param mixed $reflector Either a Reflector instance, the name of a class,
     * a doc comment or an array to use to directly populate the instance. An
     * value that evaluates to `false` will result in an empty Annotations
     * instance.
     * @throws ReflectorNotCommentedException If the given object is a Reflector
     * instance but does not contain a getDocComment() method.
     * @throws InvalidArgumentException If the given parameter is an object but
     * not a Reflector instance.
     */
    public function __construct($reflector = null)
    {
        $this->docComment = self::parseDocComment($reflector);
    }

    /**
     * Parse a doc comment from a parameter or throw an exception.
     */
    public static function parseDocComment($arg): string
    {
        if (is_object($arg)) {
            if (!($arg instanceof Reflector)) {
                throw new InvalidArgumentException();
            }

            if (!method_exists($arg, 'getDocComment')) {
                throw new ReflectorNotCommentedException();
            }

            return $arg->getDocComment();
        }

        if (class_exists($arg)) {
            $class = new ReflectionClass($arg);
            return $class->getDocComment();
        }

        return $arg;
    }

    public function addResolver(string $annotationName, string $class)
    {
        $this->getParser()->addResolverClass($annotationName, $class);
    }

    /**
     * @return \Sayla\Objects\Annotation\AnnotationParser
     */
    public function getParser(): AnnotationParser
    {
        return $this->parser ?? ($this->parser = new AnnotationParser());
    }

    /**
     * @param \Sayla\Objects\Annotation\AnnotationParser $parser
     */
    public function setParser(AnnotationParser $parser): void
    {
        $this->parser = $parser;
    }

    /**
     * @return \Sayla\Objects\Annotation\ParserResult
     */
    public function getResult(): ParserResult
    {
        if (!$this->parserResult) {
            $this->parserResult = self::getParser()->getAnnotations($this->docComment);
        }
        return $this->parserResult;
    }

    public function hasAnnotation(...$names)
    {
        if (count($names) == 0) {
            return count($this->getResult()) > 0;
        }

        while (count($names) > 0) {
            $anno = strtolower(array_shift($names));
            $exists = $this->getResult()->collect()->firstWhere('name', $anno);
            if (!$exists) {
                return false;
            }
        }

        return true;
    }

}
