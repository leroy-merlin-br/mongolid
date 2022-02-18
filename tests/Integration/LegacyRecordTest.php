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
        $embedded->_id = new ObjectID();
        $embedded->name = 'Course Class #1';
        $entity->attachToGrandsons($embedded);

        $this->assertEquals([$embedded->_id], $entity->other_arbitrary_field);
    }

    public function testShouldEmbedToAttribute(): void
    {
        $entity = new LegacyRecordUser();
        $entity->name = 'Parent User';
        $embedded = new LegacyRecordUser();
        $embedded->name = 'Embedded User';
        $entity->embed('siblings', $embedded);

        $this->assertEquals('Embedded User', $entity->siblings()->getResults()->first()->name);
    }
}
