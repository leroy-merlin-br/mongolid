<?php

use Zizaco\Mongolid\Model;
use Zizaco\Mongolid\OdmCursor;
use Zizaco\Mongolid\CachableOdmCursor;
use Mockery as m;

class CachableOdmCursorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->mongoMock = m::mock('Connection');
        $this->OdmCursor = m::mock(new _stubOdmCursor);

        $this->objA = new _stubModelForCachable;
        $this->objA->name = 'bob';

        $this->objB = new _stubModelForCachable;
        $this->objB->name = 'billy';

        $this->OdmCursor
            ->shouldReceive('toArray')
            ->with(false)
            ->andReturn([
                $this->objA,
                $this->objB
            ]);

        _stubModelForCachable::$connection = $this->mongoMock;
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

        $cachableOdmCursor = new CachableOdmCursor(['name'=>'bob'], '_stubModelForCachable');
        $result = $cachableOdmCursor->getCursor();

        $this->assertEquals( 'theCursor', $result );
    }

    public function testShouldCallOdmCursorMethod()
    {
        $this->OdmCursor
            ->shouldReceive('randomMethod')
            ->once()
            ->with(1,2,3)
            ->andReturn(true);

        $cachableOdmCursor = new CachableOdmCursor(['name'=>'bob'], '_stubModelForCachable');
        $result = $cachableOdmCursor->randomMethod(1,2,3);

        $this->assertTrue( $result );
    }

    public function testShouldGetCurrent()
    {
        $cachableOdmCursor = new CachableOdmCursor($this->OdmCursor, '_stubModelForCachable');
        $result = $cachableOdmCursor->current();

        $this->assertInstanceOf('_stubModelForCachable', $result);
    }

    public function testShouldConvertToArrayCurrent()
    {
        $cachableOdmCursor = new CachableOdmCursor($this->OdmCursor, '_stubModelForCachable');
        $result = $cachableOdmCursor->toArray();
        $should_be = array($this->objA->attributes,$this->objB->attributes);

        $this->assertEquals($should_be, $result);
    }

    public function testShouldGetFirst()
    {
        $cachableOdmCursor = new CachableOdmCursor($this->OdmCursor, '_stubModelForCachable');
        $result = $cachableOdmCursor->first();

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

        $this->assertEquals(3,$cachableOdmCursor->key());
    }

    public function testShouldSort()
    {
        $this->OdmCursor->shouldReceive('sort');

        $cachableOdmCursor = new CachableOdmCursor($this->OdmCursor, '_stubModelForCachable');
        $result = $cachableOdmCursor->sort(['name']);
    }

    public function testToJson()
    {
        $cachableOdmCursor = new CachableOdmCursor($this->OdmCursor, '_stubModelForCachable');
        $result = $cachableOdmCursor->toJson();

        $shouldBe = json_encode(array($this->objA->attributes, $this->objB->attributes));

        $this->assertEquals($shouldBe, $result);
    }
}

class _stubModelForCachable extends Model {
    protected $collection = 'test_model';

    public static $returnToWhere;

    public static function where($query = array(), $fields = array(), $cachable = false) {
        return static::$returnToWhere;
    }
}

class _stubOdmCursor {}
