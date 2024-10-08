<?php

namespace Mongolid\Tests\Integration;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Tests\Stubs\ExpirablePrice;
use Mongolid\Tests\Stubs\Legacy\LegacyRecordUser;
use Mongolid\Util\LocalDateTime;

class DateTimeCastTest extends IntegrationTestCase
{
    public function testShouldCreateAndSavePricesWithCastedAttributes(): void
    {
        // Set
        $price = new ExpirablePrice();
        $price->value = '100.0';
        $price->expires_at = DateTime::createFromFormat('d/m/Y', '02/10/2025');

        // Actions
        $price->save();

        // Assertions
        $this->assertInstanceOf(DateTime::class, $price->expires_at);
        $this->assertInstanceOf(
            UTCDateTime::class,
            $price->getOriginalDocumentAttributes()['expires_at']
        );

        $price = ExpirablePrice::first($price->_id);
        $this->assertSame('02/10/2025', $price->expires_at->format('d/m/Y'));
        $this->assertSame(
            '02/10/2025',
            LocalDateTime::get(
                $price->getOriginalDocumentAttributes()['expires_at']
            )
                ->format('d/m/Y')
        );
    }

    public function testShouldUpdatePriceWithCastedAttributes(): void
    {
        // Set
        $price = new ExpirablePrice();
        $price->value = '100.0';
        $price->expires_at = DateTime::createFromFormat('d/m/Y', '02/10/2025');

        // Actions
        $price->expires_at = DateTime::createFromFormat('d/m/Y', '02/10/2030');

        // Assertions
        $this->assertInstanceOf(DateTime::class, $price->expires_at);
        $this->assertSame('02/10/2030', $price->expires_at->format('d/m/Y'));
    }

    public function testShouldSaveAndReadLegacyRecordWithCastedAttibutes(): void
    {
        // Set
        $entity = new class extends LegacyRecordUser {
            protected array $casts = [
                'expires_at' => 'datetime',
            ];
        };
        $entity->expires_at = DateTime::createFromFormat(
            'd/m/Y',
            '02/10/2025'
        );

        // Actions
        $entity->save();

        // Assertions
        $this->assertInstanceOf(DateTime::class, $entity->expires_at);
        $this->assertInstanceOf(
            UTCDateTime::class,
            $entity->getOriginalDocumentAttributes()['expires_at']
        );
    }
}
