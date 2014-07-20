<?php namespace Mongolid\Mongolid;

use TestCase;
use Mockery as m;
use MongoId;

class SaveMethodTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldSaveForANewDocumentWithCollection()
    {
        // Arrange
        $methods = [
            'isPersistable',
            'prepareAttributes',
            'prepareOptions',
            'parseDocument',
            'fireAfterEventsTo',
            'fireBeforeEventsTo',
            'collection'
        ];

        $methods    = implode(',', $methods);
        $model      = m::mock("Mongolid\Mongolid\Model[$methods]");
        $collection = m::mock('collection');
        $mongoId    = new MongoId;

        // Expect
        $model->shouldAllowMockingProtectedMethods();

        $collection->shouldReceive('save')
            ->once()
            ->with(['foo' => 'value'], ['w' => 1])
            ->andReturn(['ok' => true, '_id' => $mongoId]);

        $model->shouldReceive('collection')
            ->once()
            ->andReturn($collection);

        $model->shouldReceive('isPersistable')
            ->once()
            ->andReturn(true);

        $model->shouldReceive('prepareAttributes')
            ->once()
            ->andReturn(['foo' => 'value']);

        $model->shouldReceive('prepareOptions')
            ->once()
            ->andReturn(['w' => 1]);

        $model->shouldReceive('parseDocument')
            ->once()
            ->with(['_id' => $mongoId]);

        $model->shouldReceive('fireBeforeEventsTo')
            ->with('saving')
            ->once()
            ->andReturn(true)
            ->shouldReceive('fireAfterEventsTo')
            ->with('saved', false)
            ->once()
            ->andReturn(true);

        // Act
        $result = $model->save();

        // Assert
        $this->assertEquals($model->_id, $mongoId);
        $this->assertTrue($result);
    }

    public function testShouldSaveForANewDocumentEmbeed()
    {
        // Arrange
        $methods = [
            'isPersistable',
            'prepareAttributes',
            'prepareOptions',
            'parseDocument',
            'fireBeforeEventsTo',
            'fireAfterEventsTo',
            'collection'
        ];

        $methods    = implode(',', $methods);
        $model      = m::mock("Mongolid\Mongolid\Model[$methods]");

        // Expect
        $model->shouldAllowMockingProtectedMethods();
        $model->shouldReceive('isPersistable')
            ->once()
            ->andReturn(false);

        $model->shouldReceive('collection')
            ->never();

        $model->shouldReceive('prepareAttributes')
            ->never();

        $model->shouldReceive('prepareOptions')
            ->never();

        $model->shouldReceive('parseDocument')
            ->never();

        $model->shouldReceive('fireEventTo')
            ->never();

        // Act
        $result = $model->save();

        // Assert
        $this->assertNull($result);
    }

    public function testShouldReturnFalseIfNotSaved()
    {
        // Arrange
        $methods = [
            'isPersistable',
            'prepareAttributes',
            'prepareOptions',
            'parseDocument',
            'fireBeforeEventsTo',
            'fireAfterEventsTo',
            'collection'
        ];

        $methods    = implode(',', $methods);
        $model      = m::mock("Mongolid\Mongolid\Model[$methods]");
        $collection = m::mock('collection');

        // Expect
        $model->shouldAllowMockingProtectedMethods();
        $collection->shouldReceive('save')
            ->once()
            ->with(['foo' => 'value'], ['w' => 1])
            ->andReturn(['ok' => false]);

        $model->shouldReceive('collection')
            ->once()
            ->andReturn($collection);

        $model->shouldReceive('isPersistable')
            ->once()
            ->andReturn(true);

        $model->shouldReceive('prepareAttributes')
            ->once()
            ->andReturn(['foo' => 'value']);

        $model->shouldReceive('prepareOptions')
            ->once()
            ->andReturn(['w' => 1]);

        $model->shouldReceive('parseDocument')
            ->never();

        $model->shouldReceive('fireBeforeEventsTo')
            ->with('saving')
            ->once()
            ->andReturn(true);

        // Act
        $result = $model->save();

        // Assert
        $this->assertFalse($result);
    }

    public function testShouldReturnFalseIfNotHasProblemWithSavingEvent()
    {
        // Arrange
        $methods = [
            'isPersistable',
            'prepareAttributes',
            'prepareOptions',
            'parseDocument',
            'fireBeforeEventsTo',
            'fireAfterEventsTo',
            'collection'
        ];

        $methods    = implode(',', $methods);
        $model      = m::mock("Mongolid\Mongolid\Model[$methods]");
        $collection = m::mock('collection');

        // Expect
        $model->shouldAllowMockingProtectedMethods();
        $collection->shouldReceive('save')
            ->never();

        $model->shouldReceive('collection')
            ->never();

        $model->shouldReceive('isPersistable')
            ->once()
            ->andReturn(true);

        $model->shouldReceive('prepareAttributes')
            ->never();

        $model->shouldReceive('prepareOptions')
            ->never();

        $model->shouldReceive('parseDocument')
            ->never();

        $model->shouldReceive('fireBeforeEventsTo')
            ->with('saving')
            ->once()
            ->andReturn(false);

        // Act
        $result = $model->save();

        // Assert
        $this->assertFalse($result);
    }

    public function testShouldReturnFalseIfSavedEventHasAError()
    {
        // Arrange
        $methods = [
            'isPersistable',
            'prepareAttributes',
            'prepareOptions',
            'parseDocument',
            'fireBeforeEventsTo',
            'fireAfterEventsTo',
            'collection'
        ];

        $methods    = implode(',', $methods);
        $model      = m::mock("Mongolid\Mongolid\Model[$methods]");
        $collection = m::mock('collection');

        // Expect
        $model->shouldAllowMockingProtectedMethods();
        $collection->shouldReceive('save')
            ->once()
            ->with(['foo' => 'value'], ['w' => 1])
            ->andReturn(['ok' => true]);

        $model->shouldReceive('collection')
            ->once()
            ->andReturn($collection);

        $model->shouldReceive('isPersistable')
            ->once()
            ->andReturn(true);

        $model->shouldReceive('prepareAttributes')
            ->once()
            ->andReturn(['foo' => 'value']);

        $model->shouldReceive('prepareOptions')
            ->once()
            ->andReturn(['w' => 1]);

        $model->shouldReceive('parseDocument')
            ->never();

        $model->shouldReceive('fireBeforeEventsTo')
            ->with('saving')
            ->once()
            ->andReturn(true)
            ->getMock()
            ->shouldReceive('fireAfterEventsTo')
            ->with('saved', false)
            ->once()
            ->andReturn(false);

        // Act
        $result = $model->save();

        // Assert
        $this->assertFalse($result);
    }
}
