<?php

namespace Mongolid\Util;

use Mockery as m;
use Mongolid\TestCase;

class CacheComponentTest extends TestCase
{
    /**
     * Current time that will be retrieved by CacheComponent::time().
     *
     * @var int
     */
    public $time = 1466710000;

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldImplementCacheComponentInterface(): void
    {
        // Arrange
        $cacheComponent = (new CacheComponent());

        // Assertion
        $this->assertInstanceOf(CacheComponentInterface::class, $cacheComponent);
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

    protected function getCacheComponent()
    {
        $test = $this;
        $cacheComponent = m::mock(CacheComponent::class.'[time]');
        $cacheComponent->shouldAllowMockingProtectedMethods();
        $cacheComponent->shouldReceive('time')
            ->andReturnUsing(function () use ($test) {
                return $test->time;
            });

        return $cacheComponent;
    }

    /**
     * Skips $seconds of time.
     */
    protected function tick($seconds)
    {
        $this->time += $seconds;
    }
}
