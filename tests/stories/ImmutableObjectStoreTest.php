<?php

namespace Sayla\Objects\Tests\Cases;

use Sayla\Objects\Attribute\Property\MapPropertyType;
use Sayla\Objects\Contract\ImmutableDataModelTrait;
use Sayla\Objects\Contract\ObjectStore;
use Sayla\Objects\DataModel;
use Sayla\Objects\DataObject;
use Sayla\Objects\DataType\DataTypeManager;
use Sayla\Objects\Tests\Support\BaseStory;

class ImmutableBookModel extends DataModel
{
    use ImmutableDataModelTrait;

    public static function resolveCandyAttribute(self $book)
    {
        return 'candy-' . $book->title . $book->getKey();
    }

    protected function determineExistence(): bool
    {
        return $this->id > 0;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->id;
    }
}

class ImmutableObjectStoreTest extends BaseStory
{
    protected function setUp()
    {
        DataObject::clearTriggerCallCount(ImmutableBookModel::class);
    }

    public function testCreate()
    {
        $storeStrategy = $this->getMockBuilder(ObjectStore::class)
            ->setMethods(['create'])
            ->getMock();
        $storeStrategy->expects($this->once())
            ->method('create')
            ->willReturnCallback(function (ImmutableBookModel $bookModel) {
                $this->assertFalse($bookModel->exists());
                $this->assertTrue($bookModel->isStoring());
                return ['id' => 99];
            });

        $dataType = $this->getDataType($storeStrategy);

        $data = $this->getRawBookData();
        /** @var ImmutableBookModel $book */
        $book = $dataType->hydrate($data);

        $newBook = $book->create();
        $this->assertNotSame($book, $newBook);
        $this->assertFalse($book->exists());
        $this->assertNull($book->id);

        $this->assertTrue($newBook->exists());
        $this->assertSame(99, $newBook->id);
        $this->assertEquals("candy-{$data['title']}99", $newBook->candy);
    }

    /**
     * @param $storeStrategy
     * @return \Sayla\Objects\DataType\StoringDataType
     */
    private function getDataType($storeStrategy): \Sayla\Objects\DataType\StoringDataType
    {
        $dataTypeManager = new DataTypeManager();
        $builder = $dataTypeManager->getBuilder(ImmutableBookModel::class)->storeStrategy($storeStrategy);
        return $builder
            ->attributeDefinitions([
                'id:pk' => ['mapTo' => '_id'],
                'title:string',
                'author:string',
                'publishDate:datetime' => ['transform.format' => 'Y-m-d', 'mapTo' => 'publish_date'],
                'candy:string' => ['map' => false]
            ])
            ->addPropertyType((new MapPropertyType())->enableAutoMapping())
            ->build();
    }

    /**
     * @return array
     */
    private function getRawBookData(): array
    {
        return ['title' => 'Better than butter', 'author' => 'Mike Sah', 'publish_date' => '2014-03-01'];
    }

    public function testDelete()
    {
        $storeStrategy = $this->getMockBuilder(ObjectStore::class)
            ->setMethods(['create', 'delete'])
            ->getMock();
        $storeStrategy->expects($this->once())
            ->method('create')
            ->willReturnCallback(function () {
                return ['id' => 99];
            });
        $storeStrategy->expects($this->once())
            ->method('delete')
            ->willReturnCallback(function (ImmutableBookModel $bookModel) {
                $this->assertTrue($bookModel->exists());
                $this->assertTrue($bookModel->isStoring());
            });

        $dataType = $this->getDataType($storeStrategy);

        $data = $this->getRawBookData();
        /** @var ImmutableBookModel $book */
        $book = $dataType->hydrate($data);
        $newBook = $book->create();

        $deletedBook = $newBook->delete();
        $this->assertNotSame($book, $deletedBook);
        $this->assertSame(99, $deletedBook->id);
        $this->assertFalse($book->exists());

    }

    public function testUpdate()
    {
        $storeStrategy = $this->getMockBuilder(ObjectStore::class)
            ->setMethods(['create', 'update'])
            ->getMock();
        $storeStrategy->expects($this->once())
            ->method('create')
            ->willReturnCallback(function () {
                return ['id' => 99];
            });
        $storeStrategy->expects($this->once())
            ->method('update')
            ->willReturnCallback(function (ImmutableBookModel $bookModel) {
                $this->assertTrue($bookModel->exists());
                $this->assertTrue($bookModel->isStoring());
            });

        $dataType = $this->getDataType($storeStrategy);

        $data = $this->getRawBookData();
        /** @var ImmutableBookModel $book */
        $book = $dataType->hydrate($data);
        $newBook = $book->create();

        $updatedBook = $newBook->update();
        $this->assertNotSame($book, $updatedBook);
        $this->assertTrue($updatedBook->exists());
        $this->assertSame(99, $updatedBook->id);
        $this->assertEquals("candy-{$data['title']}99", $updatedBook->candy);

    }
}