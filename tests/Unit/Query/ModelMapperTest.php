<?php

namespace Mongolid\Query;

use DateTime;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Model\AbstractModel;
use Mongolid\TestCase;

final class ModelMapperTest extends TestCase
{
    public function testShouldClearDynamicFieldsIfModelIsNotDynamic(): void
    {
        // Set
        $model = new class extends AbstractModel
        {
        };
        $modelMapper = new ModelMapper();
        $model->_id = 1;
        $model->name = 'John';
        $model->age = 23;
        $model->location = 'Brazil';
        $model->created_at = new UTCDateTime(); // `$model->timestamps` is false!

        // Actions
        $result = $modelMapper->map($model, ['name', 'age'], false, false);

        // Assertions
        $this->assertSame(
            [
                '_id' => 1,
                'name' => 'John',
                'age' => 23,
            ],
            $result
        );
    }

    public function testShouldClearDynamicFieldsIfModelIsNotDynamicCheckingTimestamps(): void
    {
        // Set
        $model = new class extends AbstractModel
        {
        };
        $modelMapper = new ModelMapper();
        $model->_id = 1;
        $model->name = 'John';
        $model->age = 23;
        $model->location = 'Brazil';
        $dateTime = new UTCDateTime();
        $model->created_at = $dateTime; // `$model->timestamps` is false!

        // Actions
        $result = $modelMapper->map($model, ['name', 'age'], false, true);

        // Assertions
        $this->assertSame(
            [
                '_id' => 1,
                'name' => 'John',
                'age' => 23,
                'created_at' => $dateTime,
                'updated_at' => $model->updated_at,
            ],
            $result
        );
    }

    public function testShouldNotClearDynamicFieldsIfModelIsDynamic(): void
    {
        // Set
        $model = new class extends AbstractModel
        {
        };

        $modelMapper = new ModelMapper();
        $model->_id = 1;
        $model->name = 'John';
        $model->age = 23;
        $model->location = 'Brazil';

        // Actions
        $result = $modelMapper->map($model, ['name', 'age'], true, false);

        // Assertions
        $this->assertSame(
            [
                '_id' => 1,
                'name' => 'John',
                'age' => 23,
                'location' => 'Brazil',
            ],
            $result
        );
    }

    public function testShouldClearNullFields(): void
    {
        // Set
        $model = new class extends AbstractModel
        {
        };

        $modelMapper = new ModelMapper();
        $model->_id = 1;
        $model->name = 'John';
        $model->age = null;
        $model->location = null;

        // Actions
        $result = $modelMapper->map($model, ['name', 'age'], true, false);

        // Assertions
        $this->assertSame(
            [
                '_id' => 1,
                'name' => 'John',
            ],
            $result
        );
    }

    public function testShouldGenerateAnIdIfModelDoesNotHaveOne(): void
    {
        // Set
        $model = new class extends AbstractModel
        {
        };

        $modelMapper = new ModelMapper();
        $model->name = 'John';

        // Actions
        $result = $modelMapper->map($model, [], true, true);

        // Assertions
        $this->assertSame('John', $result['name']);
        $this->assertInstanceOf(ObjectId::class, $result['_id']);
        $this->assertInstanceOf(ObjectId::class, $model->_id);
    }

    public function testShouldCastObjectId(): void
    {
        // Set
        $model = new class extends AbstractModel
        {
        };

        $modelMapper = new ModelMapper();
        $id = '5bfd396038b5fa0001462681';
        $model->_id = $id;

        // Actions
        $result = $modelMapper->map($model, [], true, true);

        // Assertions
        $this->assertInstanceOf(ObjectId::class, $result['_id']);
        $this->assertSame($model->_id, $result['_id']);
        $this->assertEquals(new ObjectId($id), $model->_id);
    }

    public function testShouldHandleTimestampsCreatingCreatedAtField(): void
    {
        // Set
        $model = new class extends AbstractModel
        {
        };

        $modelMapper = new ModelMapper();
        $model->_id = 1;
        $model->name = 'John';

        // Actions
        $result = $modelMapper->map($model, ['name', 'age'], true, true);

        // Assertions
        $this->assertSame('John', $result['name']);
        $this->assertSame(1, $result['_id']);
        $this->assertInstanceOf(UTCDateTime::class, $result['created_at']);
        $this->assertInstanceOf(UTCDateTime::class, $result['updated_at']);
        $this->assertSame($model->created_at, $result['created_at']);
        $this->assertSame($model->updated_at, $result['updated_at']);
        $this->assertSame($model->updated_at, $model->created_at);
    }

    public function testShouldHandleTimestampsOnlyUpdatingUpdatedAtField(): void
    {
        // Set
        $model = new class extends AbstractModel
        {
        };

        $modelMapper = new ModelMapper();
        $model->_id = 1;
        $model->name = 'John';
        $createdAt = new UTCDateTime(new DateTime('-2 hour'));
        $updatedAt = new UTCDateTime(new DateTime('-1 hour'));
        $model->created_at = $createdAt;
        $model->updated_at = $updatedAt;

        // Actions
        $result = $modelMapper->map($model, ['name', 'age'], true, true);

        // Assertions
        $this->assertSame('John', $result['name']);
        $this->assertSame(1, $result['_id']);
        $this->assertInstanceOf(UTCDateTime::class, $result['created_at']);
        $this->assertInstanceOf(UTCDateTime::class, $result['updated_at']);
        $this->assertSame($model->created_at, $result['created_at']);
        $this->assertSame($model->updated_at, $result['updated_at']);
        $this->assertSame($createdAt, $model->created_at);
        $this->assertNotSame($updatedAt, $model->updated_at);
        $this->assertGreaterThan($updatedAt, $model->updated_at);
    }
}
