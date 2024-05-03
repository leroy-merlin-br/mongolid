<?php
namespace Mongolid\Tests\Integration;

use MongoDB\BSON\ObjectId;
use Mongolid\LegacyRecord;
use Mongolid\Tests\Stubs\Legacy\LegacyRecordUser;
use Mongolid\Tests\Stubs\Size;

class LegacyRecordTest extends IntegrationTestCase
{
    public function testShouldAttachToAttribute(): void
    {
        $entity = new LegacyRecordUser();
        $embedded = new LegacyRecordUser();
        $embedded->_id = new ObjectId();
        $embedded->name = 'Course Class #1';
        $entity->attachToGrandsons($embedded);

        $this->assertEquals([$embedded->_id], $entity->grandsons);
    }

    public function testShouldEmbedToAttribute(): void
    {
        $entity = new LegacyRecordUser();
        $entity->name = 'Parent User';
        $embedded = new LegacyRecordUser();
        $embedded->name = 'Embedded User';
        $entity->embed('siblings', $embedded);

        $this->assertEquals('Embedded User', $entity->siblings()->first()->name);
    }

    public function testShouldFillModel(): void
    {
        $entity = new LegacyRecordUser();
        $data = [
            'name' => 'Parent User',
            'invalidField' => 'value',
        ];
        $expected = [
            'name' => 'Parent User',
        ];
        $entity->fill($data);

        $this->assertSame($expected, $entity->getAttributes());
    }

    /**
     * @requires >= PHP 8.1
     */
    public function testLegacyFillShouldNotSupportCast(): void
    {
        // Set
        $model = new class() extends LegacyRecord
        {
            protected array $casts = [
                'birthdate' => 'datetime',
            ];
        };

        // Actions
        $model = $model->fill(['birthdate' => 123456]);

        // Assertions
        $this->assertEquals($model->getDocumentAttributes()['birthdate'], 123456);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testShouldBsonSerializeWithCast(): void
    {
        // Set
        $model = new class() extends LegacyRecord
        {
            protected array $casts = [
                'size' => Size::class,
            ];
        };
        $model->size = Size::Small;

        // Actions
        $serializedModel = $model->bsonSerialize();

        // Assertions
        $this->assertSame(Size::Small->value, $serializedModel['size']);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testShouldBsonUnserializeWithCast(): void
    {
        // Set
        $model = new class() extends LegacyRecord
        {
            protected array $casts = [
                'size' => Size::class,
            ];
        };
        $model->size = Size::Small;
        $serializedModel = $model->bsonSerialize();
        $unserializedModel = new (get_class($model));

        // Actions
        $unserializedModel->bsonUnserialize($serializedModel);

        // Assertions
        $this->assertSame(Size::Small->value, $unserializedModel->getDocumentAttributes()['size']);
        $this->assertSame(Size::Small, $unserializedModel->size);
    }

    public function testShouldOverrideSetAttributeMethods(): void
    {
        $entity = new LegacyRecordUser();
        $expected = [
            'secret' => 'password_override',
        ];

        // Should be overridden by setSecretAttribute on LegacyRecordUser
        $entity->secret = 'password';

        $this->assertSame($expected, $entity->getAttributes());
    }

    public function testShouldFreshModel(): void
    {
        // Set
        $entity = new LegacyRecordUser();
        $entity->dynamic = true;
        $entity->fill([
            'name' => 'John Doe',
        ]);
        $entity->save();

        // Actions
        $entity->name = 'Jane Doe';
        $entity = $entity->fresh();

        // Assertions
        /**
         * In this test, User must have his old name after refresh because its model wasn't persisted after setting its name to Jane Doe.
         */
        $this->assertInstanceOf(LegacyRecordUser::class, $entity);
        $this->assertSame('John Doe', $entity->name);
    }
}
