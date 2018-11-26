<?php
namespace Mongolid\Model;

use Mockery as m;
use MongoDB\Driver\WriteConcern;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Model\Exception\NoCollectionNameException;
use Mongolid\Query\Builder;
use Mongolid\Schema\DynamicSchema;
use Mongolid\TestCase;
use stdClass;

class AbstractModelTest extends TestCase
{
    /**
     * @var AbstractModel
     */
    protected $model;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->model = new class() extends AbstractModel
        {
            /**
             * {@inheritdoc}
             */
            protected $collection = 'mongolid';

            public function unsetCollection()
            {
                unset($this->collection);
            }

            public function setFields($value)
            {
                $this->fields = $value;
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->model);
        parent::tearDown();
    }

    public function testShouldHaveCorrectPropertiesByDefault()
    {
        // Assertions
        $this->assertAttributeEquals(
            [
                '_id' => 'objectId',
                'created_at' => 'createdAtTimestamp',
                'updated_at' => 'updatedAtTimestamp',
            ],
            'fields',
            $this->model
        );
        $this->assertTrue($this->model->dynamic);
    }

    public function testShouldImplementModelTraits()
    {
        // Assertions
        $this->assertSame(
            [HasAttributesTrait::class, HasRelationsTrait::class],
            array_keys(class_uses(AbstractModel::class))
        );
    }

    public function testShouldSave()
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));

        // Actions
        $builder->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $builder->expects()
            ->save($this->model, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        // Assertions
        $this->assertTrue($this->model->save());
    }

    public function testShouldInsert()
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));

        // Actions
        $builder->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $builder->expects()
            ->insert($this->model, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        // Assertions
        $this->assertTrue($this->model->insert());
    }

    public function testShouldUpdate()
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));

        // Actions
        $builder->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $builder->expects()
            ->update($this->model, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        // Assertions
        $this->assertTrue($this->model->update());
    }

    public function testShouldDelete()
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));

        // Actions
        $builder->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $builder->expects()
            ->delete($this->model, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        // Assertions
        $this->assertTrue($this->model->delete());
    }

    public function testSaveShouldReturnFalseIfCollectionIsNull()
    {
        $this->model->unsetCollection();
        $this->assertFalse($this->model->save());
    }

    public function testUpdateShouldReturnFalseIfCollectionIsNull()
    {
        $this->model->unsetCollection();
        $this->assertFalse($this->model->update());
    }

    public function testInsertShouldReturnFalseIfCollectionIsNull()
    {
        $this->model->unsetCollection();
        $this->assertFalse($this->model->insert());
    }

    public function testDeleteShouldReturnFalseIfCollectionIsNull()
    {
        $this->model->unsetCollection();
        $this->assertFalse($this->model->delete());
    }

    public function testShouldGetWithWhereQuery()
    {
        // Set
        $query = ['foo' => 'bar'];
        $projection = ['some', 'fields'];
        $builder = $this->instance(Builder::class, m::mock(Builder::class));

        $cursor = m::mock(CursorInterface::class);

        // Actions
        $builder->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $builder->expects()
            ->where($query, $projection)
            ->andReturn($cursor);

        // Assertions
        $this->assertSame($cursor, $this->model->where($query, $projection));
    }

    public function testShouldGetAll()
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));

        $cursor = m::mock(CursorInterface::class);

        // Actions
        $builder->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $builder->expects()
            ->all()
            ->andReturn($cursor);

        // Assertions
        $this->assertSame($cursor, $this->model->all());
    }

    public function testShouldGetFirstWithQuery()
    {
        // Set
        $query = ['foo' => 'bar'];
        $projection = ['some', 'fields'];
        $builder = $this->instance(Builder::class, m::mock(Builder::class));

        // Actions
        $builder->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $builder->expects()
            ->first($query, $projection)
            ->andReturn($this->model);

        // Assertions
        $this->assertSame($this->model, $this->model->first($query, $projection));
    }

    public function testShouldGetFirstOrFail()
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));
        $query = ['foo' => 'bar'];
        $projection = ['some', 'fields'];

        // Actions
        $builder->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $builder->expects()
            ->firstOrFail($query, $projection)
            ->andReturn($this->model);

        // Assertions
        $this->assertSame($this->model, $this->model->firstOrFail($query, $projection));
    }

    public function testShouldGetFirstOrNewAndReturnExistingModel()
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));
        $id = 123;

        // Actions
        $builder->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $builder->expects()
            ->first($id)
            ->andReturn($this->model);

        // Assertions
        $this->assertSame($this->model, $this->model->firstOrNew($id));
    }

    public function testShouldGetFirstOrNewAndReturnNewModel()
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));
        $id = 123;

        // Actions
        $builder->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $builder->expects()
            ->first($id)
            ->andReturn(null);

        // Assertions
        $this->assertNotEquals($this->model, $this->model->firstOrNew($id));
    }

    public function testShouldGetSchemaIfFieldsIsTheClassName()
    {
        // Set
        $this->model->setFields('MySchemaClass');
        $schema = $this->instance('MySchemaClass', m::mock(DynamicSchema::class));

        // Assertions
        $this->assertSame(
            $schema,
            $this->model->getSchema()
        );
    }

    public function testShouldGetSchemaIfFieldsDescribesSchemaFields()
    {
        // Set
        $fields = ['name' => 'string', 'age' => 'int'];
        $this->model->setFields($fields);

        // Assertions
        $result = $this->model->getSchema();
        $this->assertInstanceOf(DynamicSchema::class, $result);
        $this->assertSame($fields, $result->fields);
        $this->assertSame($this->model->dynamic, $result->dynamic);
        $this->assertSame($this->model->getCollectionName(), $result->collection);
        $this->assertSame(get_class($this->model), $result->modelClass);
    }

    public function testShouldGetDataMapper()
    {
        // Set
        $model = m::mock(AbstractModel::class.'[getSchema]');
        $schema = m::mock(DynamicSchema::class.'[]');

        // Actions
        $model->shouldAllowMockingProtectedMethods();

        $model->expects()
            ->getSchema()
            ->andReturn($schema);

        // Assertions
        $result = $this->callProtected($model, 'getBuilder');
        $this->assertInstanceOf(Builder::class, $result);
        $this->assertSame($schema, $result->getSchema());
    }

    public function testShouldRaiseExceptionWhenHasNoCollectionAndTryToCallAllFunction()
    {
        $model = new class() extends AbstractModel
        {
        };

        $this->expectException(NoCollectionNameException::class);
        $model->all();
    }

    public function testShouldRaiseExceptionWhenHasNoCollectionAndTryToCallFirstFunction()
    {
        $model = new class() extends AbstractModel
        {
        };

        $this->expectException(NoCollectionNameException::class);
        $model->first();
    }

    public function testShouldRaiseExceptionWhenHasNoCollectionAndTryToCallWhereFunction()
    {
        $model = new class() extends AbstractModel
        {
        };

        $this->expectException(NoCollectionNameException::class);
        $model->where();
    }

    public function testShouldGetCollectionName()
    {
        $this->assertSame('mongolid', $this->model->getCollectionName());
    }

    public function testShouldGetSetWriteConcernInModelClass()
    {
        $this->assertSame(1, $this->model->getWriteConcern());
        $this->model->setWriteConcern(0);
        $this->assertSame(0, $this->model->getWriteConcern());
    }

    public function testShouldHaveDynamicSetters()
    {
        // Set
        $model = new class() extends AbstractModel
        {
        };

        $childObj = new stdClass();

        // Assertions
        $model->name = 'John';
        $model->age = 25;
        $model->child = $childObj;
        $this->assertSame(
            [
                'name' => 'John',
                'age' => 25,
                'child' => $childObj,
            ],
            $model->getDocumentAttributes()
        );
    }

    public function testShouldHaveDynamicGetters()
    {
        // Set
        $child = new stdClass();
        $model = new class() extends AbstractModel
        {
        };
        $model->fill(
            [
                'name' => 'John',
                'age' => 25,
                'child' => $child,
            ]
        );

        // Assertions
        $this->assertSame('John', $model->name);
        $this->assertSame(25, $model->age);
        $this->assertSame($child, $model->child);
        $this->assertSame(null, $model->nonexistant);
    }

    public function testShouldCheckIfAttributeIsSet()
    {
        // Set
        $model = new class() extends AbstractModel
        {
        };
        $model->fill(['name' => 'John', 'ignored' => null]);

        // Assertions
        $this->assertTrue(isset($model->name));
        $this->assertFalse(isset($model->nonexistant));
        $this->assertFalse(isset($model->ignored));
    }

    public function testShouldCheckIfMutatedAttributeIsSet()
    {
        // Set
        $model = new class() extends AbstractModel
        {
            /**
             * {@inheritdoc}
             */
            public $mutable = true;

            public function getNameDocumentAttribute()
            {
                return 'John';
            }
        };

        // Assertions
        $this->assertTrue(isset($model->name));
        $this->assertFalse(isset($model->nonexistant));
    }

    public function testShouldUnsetAttributes()
    {
        // Set
        $model = new class() extends AbstractModel
        {
        };
        $model->fill(
            [
                'name' => 'John',
                'age' => 25,
            ]
        );

        // Actions
        unset($model->age);
        $result = $model->getDocumentAttributes();

        // Assertions
        $this->assertSame(['name' => 'John'], $result);
    }

    public function testShouldGetAttributeFromMutator()
    {
        // Set
        $model = new class() extends AbstractModel
        {
            /**
             * {@inheritdoc}
             */
            public $mutable = true;

            public function getShortNameDocumentAttribute()
            {
                return 'Other name';
            }
        };

        // Actions
        $model->short_name = 'My awesome name';
        $result = $model->short_name;

        // Assertions
        $this->assertSame('Other name', $result);
    }

    public function testShouldIgnoreMutators()
    {
        // Set
        $model = new class() extends AbstractModel
        {
            public function getShortNameDocumentAttribute()
            {
                return 'Other name';
            }

            public function setShortNameDocumentAttribute($value)
            {
                return strtoupper($value);
            }
        };

        $model->short_name = 'My awesome name';

        // Assertions
        $this->assertSame('My awesome name', $model->short_name);
    }

    public function testShouldSetAttributeFromMutator()
    {
        // Arrange
        $model = new class() extends AbstractModel
        {
            /**
             * {@inheritdoc}
             */
            protected $mutable = true;

            public function setShortNameDocumentAttribute($value)
            {
                return strtoupper($value);
            }
        };

        $model->short_name = 'My awesome name';
        $result = $model->short_name;

        // Assert
        $this->assertSame('MY AWESOME NAME', $result);
    }
}
