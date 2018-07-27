<?php

namespace Sayla\Objects\Tests\Units;

use PHPUnit\Framework\TestCase;
use Sayla\Objects\AttributableObject;
use Sayla\Objects\Copier;

class CopierTest extends TestCase
{
    public function testCopyAttributes()
    {
        $copier = new Copier();
        $object = new AttributableObject();
        $object->name = 'Sally Fee';
        $object->title = 'Mrs';
        $copiedAttributes = $copier->copyAttributes($object);
        $this->assertSameSize($object->toArray(), $copiedAttributes);
        $this->assertSame('Sally Fee', $copiedAttributes['name']);
        $this->assertSame('Mrs', $copiedAttributes['title']);
    }

    public function testCopyDataObject()
    {
        $copier = new Copier();
        $object = new AttributableObject();
        $object->name = 'Sally Fee';
        $object->title = 'Mrs';
        $object->faveBook = new AttributableObject();
        $object->faveBook->title = 'How I wish';
        $object->faveBook->author = 'Dan Dave';
        $copiedObject = $copier->copyObject($object);
        $this->assertSame('Sally Fee', $copiedObject->name);
        $this->assertSame('Mrs', $copiedObject->title);
        $this->assertInstanceOf(AttributableObject::class, $copiedObject->faveBook);
        $this->assertSame('How I wish', $copiedObject->faveBook->title);
        $this->assertSame('Dan Dave', $copiedObject->faveBook->author);
    }
}