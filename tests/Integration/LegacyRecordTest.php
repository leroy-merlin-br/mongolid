<?php
namespace Mongolid\Tests\Integration;

use MongoDB\BSON\ObjectId;
use Mongolid\Tests\Stubs\LegacyRecordUser;

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
}
