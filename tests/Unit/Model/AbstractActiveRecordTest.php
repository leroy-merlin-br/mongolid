<?php
namespace Mongolid\Model;

use Mockery as m;
use MongoDB\Driver\WriteConcern;
use Mongolid\Cursor\CursorInterface;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Exception\NoCollectionNameException;
use Mongolid\Schema\AbstractSchema;
use Mongolid\Schema\DynamicSchema;
use Mongolid\TestCase;
use stdClass;

class AbstractActiveRecordTest extends TestCase
{
    /**
     * @var AbstractActiveRecord
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->entity = new class() extends AbstractActiveRecord
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
        unset($this->entity);
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
            $this->entity
        );
        $this->assertTrue($this->entity->dynamic);
    }

    public function testShouldImplementModelTraits()
    {
        // Assertions
        $this->assertSame(
            [HasAttributesTrait::class, HasRelationsTrait::class],
            array_keys(class_uses(AbstractActiveRecord::class))
        );
    }

    public function testShouldSave()
    {
        // Set
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        // Actions
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->save($this->entity, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        // Assertions
        $this->assertTrue($this->entity->save());
    }

    public function testShouldInsert()
    {
        // Set
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        // Actions
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->insert($this->entity, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        // Assertions
        $this->assertTrue($this->entity->insert());
    }

    public function testShouldUpdate()
    {
        // Set
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        // Actions
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->update($this->entity, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        // Assertions
        $this->assertTrue($this->entity->update());
    }

    public function testShouldDelete()
    {
        // Set
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        // Actions
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->delete($this->entity, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        // Assertions
        $this->assertTrue($this->entity->delete());
    }

    public function testSaveShouldReturnFalseIfCollectionIsNull()
    {
        $this->entity->unsetCollection();
        $this->assertFalse($this->entity->save());
    }

    public function testUpdateShouldReturnFalseIfCollectionIsNull()
    {
        $this->entity->unsetCollection();
        $this->assertFalse($this->entity->update());
    }

    public function testInsertShouldReturnFalseIfCollectionIsNull()
    {
        $this->entity->unsetCollection();
        $this->assertFalse($this->entity->insert());
    }

    public function testDeleteShouldReturnFalseIfCollectionIsNull()
    {
        $this->entity->unsetCollection();
        $this->assertFalse($this->entity->delete());
    }

    public function testShouldGetWithWhereQuery()
    {
        // Set
        $query = ['foo' => 'bar'];
        $projection = ['some', 'fields'];
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        $cursor = m::mock(CursorInterface::class);

        // Actions
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->where($query, $projection, true)
            ->andReturn($cursor);

        // Assertions
        $this->assertSame($cursor, $this->entity->where($query, $projection, true));
    }

    public function testShouldGetAll()
    {
        // Set
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        $cursor = m::mock(CursorInterface::class);

        // Actions
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->all()
            ->andReturn($cursor);

        // Assertions
        $this->assertSame($cursor, $this->entity->all());
    }

    public function testShouldGetFirstWithQuery()
    {
        // Set
        $query = ['foo' => 'bar'];
        $projection = ['some', 'fields'];
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        // Actions
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->first($query, $projection, true)
            ->andReturn($this->entity);

        // Assertions
        $this->assertSame($this->entity, $this->entity->first($query, $projection, true));
    }

    public function testShouldGetFirstOrFail()
    {
        // Set
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));
        $query = ['foo' => 'bar'];
        $projection = ['some', 'fields'];

        // Actions
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->firstOrFail($query, $projection, true)
            ->andReturn($this->entity);

        // Assertions
        $this->assertSame($this->entity, $this->entity->firstOrFail($query, $projection, true));
    }

    public function testShouldGetFirstOrNewAndReturnExistingModel()
    {
        // Set
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));
        $id = 123;

        // Actions
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->first($id)
            ->andReturn($this->entity);

        // Assertions
        $this->assertSame($this->entity, $this->entity->firstOrNew($id));
    }

    public function testShouldGetFirstOrNewAndReturnNewModel()
    {
        // Set
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));
        $id = 123;

        // Actions
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->first($id)
            ->andReturn(null);

        // Assertions
        $this->assertNotEquals($this->entity, $this->entity->firstOrNew($id));
    }

    public function testShouldGetSchemaIfFieldsIsTheClassName()
    {
        // Set
        $this->entity->setFields('MySchemaClass');
        $schema = $this->instance('MySchemaClass', m::mock(AbstractSchema::class));

        // Assertions
        $this->assertSame(
            $schema,
            $this->entity->getSchema()
        );
    }

    public function testShouldGetSchemaIfFieldsDescribesSchemaFields()
    {
        // Set
        $fields = ['name' => 'string', 'age' => 'int'];
        $this->entity->setFields($fields);

        // Assertions
        $result = $this->entity->getSchema();
        $this->assertInstanceOf(AbstractSchema::class, $result);
        $this->assertSame($fields, $result->fields);
        $this->assertSame($this->entity->dynamic, $result->dynamic);
        $this->assertSame($this->entity->getCollectionName(), $result->collection);
        $this->assertSame(get_class($this->entity), $result->entityClass);
    }

    public function testShouldGetDataMapper()
    {
        // Set
        $entity = m::mock(AbstractActiveRecord::class.'[getSchema]');
        $schema = m::mock(AbstractSchema::class.'[]');

        // Actions
        $entity->shouldAllowMockingProtectedMethods();

        $entity->expects()
            ->getSchema()
            ->andReturn($schema);

        // Assertions
        $result = $this->callProtected($entity, 'getDataMapper');
        $this->assertInstanceOf(DataMapper::class, $result);
        $this->assertSame($schema, $result->getSchema());
    }

    public function testShouldRaiseExceptionWhenHasNoCollectionAndTryToCallAllFunction()
    {
        $entity = new class() extends AbstractActiveRecord
        {
        };

        $this->expectException(NoCollectionNameException::class);
        $entity->all();
    }

    public function testShouldRaiseExceptionWhenHasNoCollectionAndTryToCallFirstFunction()
    {
        $entity = new class() extends AbstractActiveRecord
        {
        };

        $this->expectException(NoCollectionNameException::class);
        $entity->first();
    }

    public function testShouldRaiseExceptionWhenHasNoCollectionAndTryToCallWhereFunction()
    {
        $entity = new class() extends AbstractActiveRecord
        {
        };

        $this->expectException(NoCollectionNameException::class);
        $entity->where();
    }

    public function testShouldGetCollectionName()
    {
        $this->assertSame('mongolid', $this->entity->getCollectionName());
    }

    public function testShouldGetSetWriteConcernInActiveRecordClass()
    {
        $this->assertSame(1, $this->entity->getWriteConcern());
        $this->entity->setWriteConcern(0);
        $this->assertSame(0, $this->entity->getWriteConcern());
    }

    public function testShouldHaveDynamicSetters()
    {
        // Set
        $model = new class() extends AbstractActiveRecord
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
        $model = new class() extends AbstractActiveRecord
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
        $model = new class() extends AbstractActiveRecord
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
        $model = new class() extends AbstractActiveRecord
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
        $model = new class() extends AbstractActiveRecord
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
        $model = new class() extends AbstractActiveRecord
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
        $model = new class() extends AbstractActiveRecord
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
        $model = new class() extends AbstractActiveRecord
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
