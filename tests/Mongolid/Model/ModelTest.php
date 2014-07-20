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
        $model = Ioc::make('Mongolid\Mongolid\Model');

        $result = $this->callProtected($model, 'fireBeforeEventsTo', ['saving', true]);

        $this->assertTrue($result);

        $model->_id = new \MongoId;

        $result = $this->callProtected($model, 'fireAfterEventsTo', ['saved', true]);

        $this->assertTrue($result);
    }

    public function testShouldGetNewInstance()
    {
        $model = \Mongolid\Mongolid\Model::newInstance();

        $this->assertTrue($model instanceof \Mongolid\Mongolid\Model);
    }

    public function testShouldIfIsset()
    {
        $model = Ioc::make('Mongolid\Mongolid\Model');

        $model->_id = 'id';

        $this->assertTrue(isset($model->_id));
        $this->assertFalse(isset($model->name));
    }

    public function testShouldIfUnset()
    {
        $model = Ioc::make('Mongolid\Mongolid\Model');

        $model->_id = 'id';

        unset($model->_id);

        $this->assertFalse(isset($model->_id));
    }

    public function testShouldToString()
    {
        $model = Ioc::make('Mongolid\Mongolid\Model');

        $model->_id = 'id';


        $this->assertEquals('{"_id":"id"}', (string)$model);
    }

    public function testShouldGetAttributes()
    {
        $model = Ioc::make('Mongolid\Mongolid\Model');

        $model->_id = 'id';

        $this->assertEquals('id', $model->getAttribute('_id'));

        $this->assertEquals(['_id' => 'id'], $model->getAttribute('attributes'));

        $this->assertNull($model->getAttribute('non_attr'));
    }
}
