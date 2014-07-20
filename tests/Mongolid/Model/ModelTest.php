<?php namespace Mongolid\Mongolid;

use TestCase;
use Mockery as m;
use Mongolid\Mongolid\Container\Ioc;
use MongoId;

class ModelTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldVerifyIfPersistable()
    {
        $model = Ioc::make('Mongolid\Mongolid\Model');

        // now should be null $this->collection
        $this->assertFalse($this->callProtected($model, 'isPersistable'));
        $model->setCollectionName('sadasd');

        $this->assertTrue($this->callProtected($model, 'isPersistable'));
    }

    public function testShouldTestFireEventMethod()
    {
        // Arrange
        $model = Ioc::make('Mongolid\Mongolid\Model');

        // Act
        $result = $this->callProtected($model, 'fireModelEvent', [false]);

        // Assert
        $this->assertTrue($result);
    }

    public function testShouldPrepareOptions()
    {
        // Arrange
        $model = Ioc::make('Mongolid\Mongolid\Model');

        // Act
        $options = $this->callProtected($model, 'prepareOptions');

        // Assert
        $this->assertEquals(['w' => 1], $options);
    }

    public function testShouldParseDocument()
    {
        // Arrange
        $model = Ioc::make('Mongolid\Mongolid\Model');
        $document = [
            '_id'   => new \MongoId,
            'name'  => 'Bacon',
            'price' => 10.50,
        ];

        // Act
        $result = $this->callProtected($model, 'parseDocument', [$document]);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals($document, $model->attributes());
    }

    public function testShouldReturnFalseIfNotArrayInParseDocument()
    {
        // Arrange
        $model = Ioc::make('Mongolid\Mongolid\Model');
        $document = 'non array';

        // Act
        $result = $this->callProtected($model, 'parseDocument', [$document]);

        // Assert
        $this->assertFalse($result);
    }

    public function testShouldTestFireToMethod()
    {
        // Arrange
        $model = Ioc::make('Mongolid\Mongolid\Model');

        // Act
        $result = $this->callProtected($model, 'fireBeforeEventsTo', ['saving', true]);

        // Assert
        $this->assertTrue($result);

        // Arrange
        $model->_id = new \MongoId;

        // Act
        $result = $this->callProtected($model, 'fireAfterEventsTo', ['saved', true]);

        // Assert
        $this->assertTrue($result);
    }

    public function testShouldGetNewInstance()
    {
        // Arrange
        $model = \Mongolid\Mongolid\Model::newInstance();

        // Assert
        $this->assertTrue($model instanceof \Mongolid\Mongolid\Model);
    }

    public function testShouldIfIsset()
    {
        // Arrange
        $model = Ioc::make('Mongolid\Mongolid\Model');
        $model->_id = 'id';

        // Assert
        $this->assertTrue(isset($model->_id));
        $this->assertFalse(isset($model->name));
    }

    public function testShouldIfUnset()
    {
        // Arrange
        $model = Ioc::make('Mongolid\Mongolid\Model');
        $model->_id = 'id';

        // Act
        unset($model->_id);

        // Assert
        $this->assertFalse(isset($model->_id));
    }

    public function testShouldToString()
    {
        // Arrange
        $model      = Ioc::make('Mongolid\Mongolid\Model');
        $model->_id = 'id';

        // Assert
        $this->assertEquals('{"_id":"id"}', (string)$model);
    }

    public function testShouldGetAttributes()
    {
        // Arrange
        $model = Ioc::make('Mongolid\Mongolid\Model');
        $model->_id = 'id';

        // Assert
        $this->assertEquals('id', $model->getAttribute('_id'));
        $this->assertEquals(['_id' => 'id'], $model->getAttribute('attributes'));
        $this->assertNull($model->getAttribute('non_attr'));
    }

    public function testShouldGetRawCollection()
    {
        // Arrange
        $model          = m::mock('Mongolid\Mongolid\Model[collection]');
        $mockCollection = m::mock('cool');

        // Expect

        $model->shouldAllowMockingProtectedMethods();
        $model->shouldReceive('collection')
            ->once()
            ->andReturn($mockCollection);

        // Act
        $result = $model->rawCollection();

        // Assert
        $this->assertEquals($mockCollection, $result);
    }

    public function testShouldGetCollectionMethod()
    {
        // Arrange
        $model          = m::mock('Mongolid\Mongolid\Model[db]');
        $mockCollection = m::mock('cool');

        // Expect
        $model->shouldAllowMockingProtectedMethods();

        $model->setCollectionName('collection');

        $model->shouldReceive('db')
            ->once()
            ->andReturn($mockCollection);

        $mockCollection->collection = 'cool';

        // Act
        $result = $this->callProtected($model, 'collection');

        // Assert
        $this->assertEquals('cool', $result);
    }

    public function testShouldGetDBConnection()
    {
        // Arrange
        $model          = Ioc::make('Mongolid\Mongolid\Model');
        $mockConnection = m::mock('Mongolid\Mongolid\Connection\Connection[createConnection]');
        $mockConnection->database = 'databaseobject';

        $model->setDatabaseName('database');

        // Expect
        $mockConnection->shouldReceive('createConnection')
            ->once()
            ->andReturn(m::self());

        Ioc::instance('Mongolid\Mongolid\Connection\Connection', $mockConnection);

        // Act
        $result = $this->callProtected($model, 'db');

        // Assert
        $this->assertEquals('databaseobject', $result);
    }
}
