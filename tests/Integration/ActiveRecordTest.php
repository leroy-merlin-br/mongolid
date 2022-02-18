<?php

namespace Mongolid\Tests\Integration;

use MongoDB\BSON\ObjectId;
use Mongolid\Tests\Stubs\LegacyEmbeddedUser;

class ActiveRecordTest extends IntegrationTestCase
{
    public function testShouldAttachToAttribute(): void
    {
        $entity = new LegacyEmbeddedUser();
        $embedded = new LegacyEmbeddedUser();
        $embedded->_id = new ObjectID();
        $embedded->name = 'Course Class #1';
        $entity->attachToCourseClass($embedded);

        $this->assertEquals([$embedded->_id], $entity->courseClass);
    }

    public function testShouldEmbedToAttribute(): void
    {
        $entity = new LegacyEmbeddedUser();
        $embedded = new LegacyEmbeddedUser();
        $embedded->name = 'Course Class #1';
        $entity->embedToSiblings($embedded);

        $this->assertEquals('Course Class #1', $entity->siblings()->getResults()->first()->name);
    }
}
