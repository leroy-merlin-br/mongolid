<?php

namespace Mongolid\Serializer;

use Mockery as m;
use Mongolid\Serializer\Type\Converter;
use TestCase;

/**
 * Test case for Serializer class.
 */
class SerializerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testSerializeShouldCallConvertAndReturnStringSuccessfully()
    {
        $converter = m::mock(Converter::class);
        $serializer = new Serializer($converter);

        $attributes = ['some', 'attributes'];
        $replaced = ['awsome', 'attrs'];

        $converter->shouldReceive('toDomainTypes')
            ->with($attributes)
            ->once()
            ->andReturn($replaced);

        $this->assertEquals(
            serialize($replaced),
            $serializer->serialize($attributes)
        );
    }

    public function testUnserializeShouldParseStringAndCallConverterSuccessfully()
    {
        $converter = m::mock(Converter::class);
        $serializer = new Serializer($converter);

        $attributes = ['some', 'attributes'];
        $replaced = ['awsome', 'attrs'];

        $converter->shouldReceive('toMongoTypes')
            ->with($attributes)
            ->once()
            ->andReturn($replaced);

        $this->assertEquals(
            $replaced,
            $serializer->unserialize(serialize($attributes))
        );
    }

    public function testConvertShouldConvertObjectsToDomainTypes()
    {
        $converter = m::mock(Converter::class);
        $serializer = new Serializer($converter);

        $attributes = ['some', 'attributes'];
        $replaced = ['awsome', 'attrs'];

        $converter->shouldReceive('toDomainTypes')
            ->with($attributes)
            ->once()
            ->andReturn($replaced);

        $this->assertEquals($replaced, $serializer->convert($attributes));
    }

    public function testUnconvertShouldConvertObjectsToMongoTypes()
    {
        $converter = m::mock(Converter::class);
        $serializer = new Serializer($converter);

        $attributes = ['awsome', 'attrs'];
        $replaced = ['some', 'attributes'];

        $converter->shouldReceive('toMongoTypes')
            ->with($attributes)
            ->once()
            ->andReturn($replaced);

        $this->assertEquals($replaced, $serializer->unconvert($attributes));
    }
}
