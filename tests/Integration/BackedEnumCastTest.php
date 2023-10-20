<?php

namespace Mongolid\Tests\Integration;

use Mongolid\Tests\Stubs\Box;
use Mongolid\Tests\Stubs\Legacy\Box as LegacyBox;
use Mongolid\Tests\Stubs\Size;

/**
 * @requires PHP >= 8.1
 */
class BackedEnumCastTest extends IntegrationTestCase
{
    public function testShouldCreateAndSaveBoxWithCastedAttributes(): void
    {
        // Set
        $box = new Box();
        $box->box_size = Size::Big;

        // Actions
        $box->save();

        // Assertions
        $this->assertEquals(Size::Big, $box->box_size);
        $this->assertEquals(
            'big',
            $box->getOriginalDocumentAttributes()['box_size']
        );
    }

    public function testShouldUpdateBoxWithCastedAttributes(): void
    {
        // Set
        $box = new Box();
        $box->box_size = Size::Small;

        // Actions
        $box->update();

        // Assertions
        $this->assertEquals(Size::Small, $box->box_size);
        $this->assertEquals(
            'small',
            $box->getOriginalDocumentAttributes()['box_size']
        );
    }

    public function testShouldCreateAndSaveLegacyBoxWithCastedAttributes(): void
    {
        // Set
        $legacyBox = new LegacyBox();
        $legacyBox->box_size = Size::Big;

        // Actions
        $legacyBox->save();

        // Assertions
        $this->assertEquals(Size::Big, $legacyBox->box_size);
        $this->assertEquals(
            'big',
            $legacyBox->getOriginalDocumentAttributes()['box_size']
        );
    }

    public function testShouldUpdateLegacyBoxWithCastedAttributes(): void
    {
        // Set
        $legacyBox = new LegacyBox();
        $legacyBox->box_size = Size::Small;

        // Actions
        $legacyBox->save();

        // Assertions
        $this->assertEquals(Size::Small, $legacyBox->box_size);
        $this->assertEquals(
            'small',
            $legacyBox->getOriginalDocumentAttributes()['box_size']
        );
    }
}
