<?php

use Zizaco\Mongolid\Model;
use Zizaco\Mongolid\OdmCursor;
use Mockery as m;

class OdmCursorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->mongoMock = m::mock('Connection');
        //$this->productsCollection = m::mock('Collection');
        //$this->categoriesCollection = m::mock('Collection');
        $this->mongoCursor = m::mock(new _stubMongoCursor);

        _stubModel::$connection = $this->mongoMock;
    }

    public function tearDown()
    {
        m::close();

        _stubModel::$connection = null;
    }

    public function testShouldCallRealCursorMethod()
    {
        $this->mongoCursor
            ->shouldReceive('randomMethod')
            ->once()
            ->with(1,2,3)
            ->andReturn(true);

        // If you call a method that doesn't exists in the OdmCursor it should
        // try to run the method with the same name of the real MongoCursor.
        $odmCursor = new OdmCursor($this->mongoCursor, '_stubModel');
        $result = $odmCursor->randomMethod(1,2,3);

        $this->assertTrue( $result );
    }

    public function testShouldGetCursor()
    {
        $odmCursor = new OdmCursor($this->mongoCursor, '_stubModel');
        $result = $odmCursor->getCursor();

        $this->assertEquals( $this->mongoCursor, $result );
    }

    public function testShouldRewind()
    {
        $this->mongoCursor->shouldReceive('rewind');

        $odmCursor = new OdmCursor($this->mongoCursor, '_stubModel');
        $odmCursor->rewind();
    }

    public function testShouldGetCurrent()
    {
        $this->mongoCursor
            ->shouldReceive('current')
            ->andReturn(['name'=>'bob','occupation'=>'coder']);

        $odmCursor = new OdmCursor($this->mongoCursor, '_stubModel');
        $result = $odmCursor->current();

        $this->assertInstanceOf('_stubModel', $result);
        $this->assertEquals('bob', $result->name);
        $this->assertEquals('coder', $result->occupation);
    }

    public function testToArray()
    {
        $this->mongoCursor
            ->shouldReceive('current')
            ->andReturn(['name'=>'bob','occupation'=>'coder']);
        $this->mongoCursor
            ->shouldReceive('limit');

        $odmCursor = new OdmCursor($this->mongoCursor, '_stubModel');
        $result = $odmCursor->toArray(true, 5);

        $this->assertEquals(5, count($result));
        $this->assertEquals($result[0], ['name'=>'bob','occupation'=>'coder']);
    }

    public function testShouldGetFirst()
    {
        $this->mongoCursor
            ->shouldReceive('rewind');
        $this->mongoCursor
            ->shouldReceive('current')
            ->andReturn(['name'=>'bob','occupation'=>'coder']);

        $odmCursor = new OdmCursor($this->mongoCursor, '_stubModel');
        $result = $odmCursor->current();

        $this->assertInstanceOf('_stubModel', $result);
        $this->assertEquals('bob', $result->name);
        $this->assertEquals('coder', $result->occupation);
    }

    public function testGoNextAndGetKey()
    {
        $odmCursor = new OdmCursor($this->mongoCursor, '_stubModel');

        $odmCursor->next();
        $odmCursor->next();
        $odmCursor->next();

        $this->assertEquals(3,$odmCursor->key());
    }

    public function testShouldCheckIfValid()
    {
        $this->mongoCursor
            ->shouldReceive('valid')
            ->andReturn(true);

        $odmCursor = new OdmCursor($this->mongoCursor, '_stubModel');
        $this->assertTrue($odmCursor->valid());
    }

    public function testShouldCount()
    {
        $this->mongoCursor
            ->shouldReceive('count')
            ->andReturn(5);

        $odmCursor = new OdmCursor($this->mongoCursor, '_stubModel');
        $this->assertEquals(5, $odmCursor->count());
    }

    public function testShouldSort()
    {
        $this->mongoCursor->validCount = 1;
        $this->mongoCursor
            ->shouldReceive('sort')
            ->once() // Should be called ONCE!
            ->with(['name'])
            ->andReturn(5);

        $odmCursor = new OdmCursor($this->mongoCursor, '_stubModel');
        $odmCursor->sort(['name']); // This sort should hit the MongoCursor

        // If the ammount of results that came from the actual MongoCursor
        // is zero. The sort cannot be called in the cursor.
        $this->mongoCursor
            ->shouldReceive('count')
            ->andReturn(0);
        $odmCursor->sort(['name']); // This sort should not hit the MongoCursor, this explains that 'ONCE' above.
    }

    public function testToJson()
    {
        $this->mongoCursor
            ->shouldReceive('current')
            ->andReturn(['name'=>'bob','occupation'=>'coder']);
        $this->mongoCursor
            ->shouldReceive('limit');

        $odmCursor = new OdmCursor($this->mongoCursor, '_stubModel');
        $result = $odmCursor->toJson();

        $shouldBe =
        '['.
        '{"name":"bob","occupation":"coder"}'.
        '{"name":"bob","occupation":"coder"}'.
        '{"name":"bob","occupation":"coder"}'.
        '{"name":"bob","occupation":"coder"}'.
        '{"name":"bob","occupation":"coder"}'.
        ']'; 

        $this->assertEquals($shouldBe, $result);
    }
}

class _stubModel extends Model {
    protected $collection = 'test_model';
}

class _stubMongoCursor {

    public $validCount = 5;

    public function randomMethod()
    {
        return true;
    }

    public function valid()
    {
        $this->validCount--;
        return $this->validCount >= 0;
    }

    public function next() {}

    public function limit() {}

    public function rewind() {}

    public function count() { return $this->validCount; }
}
