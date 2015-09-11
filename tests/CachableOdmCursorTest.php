<?php

use Mockery as m;
use Zizaco\Mongolid\CachableOdmCursor;
use Zizaco\Mongolid\Model;

class CachableOdmCursorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->mongoMock = m::mock('Connection');
        $this->OdmCursor = m::mock(new _stubOdmCursor);

        $this->objA             = new _stubModelForCachable;
        $this->objA->name       = 'bob';
        $this->objA->id         = 100;
        $this->objA->occupation = 'coder';

        $this->objB             = new _stubModelForCachable;
        $this->objB->name       = 'billy';
        $this->objB->id         = 200;
        $this->objB->occupation = 'qa';

        $this->OdmCursor
            ->shouldReceive('toArray')
            ->with(false)
            ->andReturn(
                [
                    $this->objA,
                    $this->objB,
                ]
            );

        _stubModelForCachable::$connection    = $this->mongoMock;
        _stubModelForCachable::$returnToWhere = $this->OdmCursor;
    }

    public function tearDown()
    {
        m::close();

        _stubModelForCachable::$connection = null;
    }

    public function testShouldGetCursor()
    {
        $this->OdmCursor
            ->shouldReceive('getCursor')
            ->once()
            ->andReturn('theCursor');

        $cachableOdmCursor = new CachableOdmCursor(['name' => 'bob'], '_stubModelForCachable');
        $result            = $cachableOdmCursor->getCursor();

        $this->assertEquals('theCursor', $result);
    }

    public function testShouldCallOdmCursorMethod()
    {
        $this->OdmCursor
            ->shouldReceive('randomMethod')
            ->once()
            ->with(1, 2, 3)
            ->andReturn(true);

        $cachableOdmCursor = new CachableOdmCursor(['name' => 'bob'], '_stubModelForCachable');
        $result            = $cachableOdmCursor->randomMethod(1, 2, 3);

        $this->assertTrue($result);
    }

    public function testShouldGetCurrent()
    {
        $cachableOdmCursor = new CachableOdmCursor($this->OdmCursor, '_stubModelForCachable');
        $result            = $cachableOdmCursor->current();

        $this->assertInstanceOf('_stubModelForCachable', $result);
    }

    public function testShouldConvertToArrayCurrent()
    {
        $cachableOdmCursor = new CachableOdmCursor($this->OdmCursor, '_stubModelForCachable');
        $result            = $cachableOdmCursor->toArray();
        $should_be         = [$this->objA->attributes, $this->objB->attributes];

        $this->assertEquals($should_be, $result);
    }

    public function testShouldGetFirst()
    {
        $cachableOdmCursor = new CachableOdmCursor($this->OdmCursor, '_stubModelForCachable');
        $result            = $cachableOdmCursor->first();

        $this->assertEquals($this->objA, $result);
    }

    public function testShouldRewind()
    {
        $this->OdmCursor->shouldReceive('rewind');

        $cachableOdmCursor = new CachableOdmCursor($this->OdmCursor, '_stubModelForCachable');
        $cachableOdmCursor->rewind();
    }

    public function testGoNextAndGetKey()
    {
        $cachableOdmCursor = new CachableOdmCursor($this->OdmCursor, '_stubModelForCachable');

        $cachableOdmCursor->next();
        $cachableOdmCursor->next();
        $cachableOdmCursor->next();

        $this->assertEquals(3, $cachableOdmCursor->key());
    }

    public function testShouldSort()
    {
        $this->OdmCursor->shouldReceive('sort');

        $cachableOdmCursor = new CachableOdmCursor($this->OdmCursor, '_stubModelForCachable');
        $result            = $cachableOdmCursor->sort(['name']);
    }

    public function testShouldList()
    {
        $cachableOdmCursor = new CachableOdmCursor($this->OdmCursor, '_stubModelForCachable');

        $this->assertEquals(
            ['bob', 'billy'],
            $cachableOdmCursor->lists('name')
        );
    }

    public function testShouldListWithKey()
    {
        $cachableOdmCursor = new CachableOdmCursor($this->OdmCursor, '_stubModelForCachable');

        $this->assertEquals(
            [100 => 'bob', 200 => 'billy'],
            $cachableOdmCursor->lists('name', 'id')
        );

        $this->assertEquals(
            ['bob' => 'coder', 'billy' => 'qa'],
            $cachableOdmCursor->lists('occupation', 'name')
        );
    }

    public function testToJson()
    {
        $cachableOdmCursor = new CachableOdmCursor($this->OdmCursor, '_stubModelForCachable');
        $result            = $cachableOdmCursor->toJson();

        $shouldBe = json_encode([$this->objA->attributes, $this->objB->attributes]);

        $this->assertEquals($shouldBe, $result);
    }
}

class _stubModelForCachable extends Model
{
    protected $collection = 'test_model';

    public static $returnToWhere;

    public static function where($query = [], $fields = [], $cachable = false)
    {
        return static::$returnToWhere;
    }
}

class _stubOdmCursor
{
}
