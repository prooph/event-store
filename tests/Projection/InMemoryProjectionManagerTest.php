<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Projection;

use Prooph\EventStore\EventStore;
use Prooph\EventStore\EventStoreDecorator;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\NonTransactionalInMemoryEventStore;
use Prooph\EventStore\Projection\InMemoryProjectionManager;

class InMemoryProjectionManagerTest extends AbstractProjectionManagerTest
{
    /**
     * @var InMemoryProjectionManager
     */
    protected $projectionManager;

    protected function setUp()
    {
        $this->projectionManager = new InMemoryProjectionManager(new InMemoryEventStore());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_event_store_instance_passed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $eventStore = $this->prophesize(EventStore::class);

        new InMemoryProjectionManager($eventStore->reveal());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_wrapped_event_store_instance_passed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $eventStore = $this->prophesize(EventStore::class);
        $wrappedEventStore = $this->prophesize(EventStoreDecorator::class);
        $wrappedEventStore->getInnerEventStore()->willReturn($eventStore->reveal())->shouldBeCalled();

        new InMemoryProjectionManager($wrappedEventStore->reveal());
    }

    /**
     * @test
     */
    public function it_allows_non_transactional_event_store_instance(): void
    {
        $eventStore = new NonTransactionalInMemoryEventStore();
        $manager = new InMemoryProjectionManager($eventStore);

        $this->assertInstanceOf(InMemoryProjectionManager::class, $manager);
    }

    /**
     * @test
     */
    public function it_cannot_delete_projections(): void
    {
        $this->expectException(RuntimeException::class);

        $this->projectionManager->deleteProjection('foo', true);
    }

    /**
     * @test
     */
    public function it_cannot_reset_projections(): void
    {
        $this->expectException(RuntimeException::class);

        $this->projectionManager->resetProjection('foo');
    }

    /**
     * @test
     */
    public function it_cannot_stop_projections(): void
    {
        $this->expectException(RuntimeException::class);

        $this->projectionManager->stopProjection('foo');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_trying_to_delete_non_existing_projection(): void
    {
        $this->markTestSkipped('Deleting a projection is not supported in ' . InMemoryProjectionManager::class);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_trying_to_reset_non_existing_projection(): void
    {
        $this->markTestSkipped('Resetting a projection is not supported in ' . InMemoryProjectionManager::class);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_trying_to_stop_non_existing_projection(): void
    {
        $this->markTestSkipped('Stopping a projection is not supported in ' . InMemoryProjectionManager::class);
    }

    /**
     * @test
     */
    public function it_does_not_fail_deleting_twice(): void
    {
        $this->markTestSkipped('Deleting a projection is not supported in ' . InMemoryProjectionManager::class);
    }

    /**
     * @test
     */
    public function it_does_not_fail_resetting_twice(): void
    {
        $this->markTestSkipped('Resetting a projection is not supported in ' . InMemoryProjectionManager::class);
    }

    /**
     * @test
     */
    public function it_does_not_fail_stopping_twice(): void
    {
        $this->markTestSkipped('Stopping a projection is not supported in ' . InMemoryProjectionManager::class);
    }
}
