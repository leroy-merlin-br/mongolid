<?php

namespace Mongolid\Util;

use Mockery as m;
use Mockery\MockInterface;
use Mongolid\TestCase;

class CacheComponentTest extends TestCase
{
    /**
     * Current time that will be retrieved by CacheComponent::time().
     */
    private int $time = 1466710000;

    public function testShouldImplementCacheComponentInterface(): void
    {
        // Arrange
        $cacheComponent = (new CacheComponent());

        // Assertion
        $this->assertInstanceOf(
            CacheComponentInterface::class,
            $cacheComponent
        );
    }

    public function testShouldPutAndRetrieveValues(): void
    {
        // Arrange
        $cacheComponent = $this->getCacheComponent();

        // Assertion
        $cacheComponent->put('saveThe', 'bacon', 1); // 1 minute of ttl
        $this->tick(30); // After 30 seconds
        $this->assertTrue($cacheComponent->has('saveThe'));
        $this->assertEquals('bacon', $cacheComponent->get('saveThe'));
    }

    public function testShouldExpireValues(): void
    {
        // Arrange
        $cacheComponent = $this->getCacheComponent();
        $cacheComponent->put('saveThe', 'bacon', 1); // 1 minute of ttl

        // Act
        $this->tick(61); // After 61 seconds

        // Assertion
        $this->assertFalse($cacheComponent->has('saveThe'));
        $this->assertNull($cacheComponent->get('saveThe'));
    }

    protected function getCacheComponent(): MockInterface
    {
        $cacheComponent = m::mock(CacheComponent::class . '[time]');
        $cacheComponent->shouldAllowMockingProtectedMethods();
        $cacheComponent->shouldReceive('time')
            ->andReturnUsing(fn () => $this->time);

        return $cacheComponent;
    }

    /**
     * Skips $seconds of time.
     */
    protected function tick($seconds): void
    {
        $this->time += $seconds;
    }
}
