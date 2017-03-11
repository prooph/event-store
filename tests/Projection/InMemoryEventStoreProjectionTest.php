<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Projection;

use Prooph\EventStore\EventStore;
use Prooph\EventStore\EventStoreDecorator;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Projection\InMemoryEventStoreProjection;
use Prooph\EventStore\Projection\InMemoryProjectionManager;

class InMemoryEventStoreProjectionTest extends AbstractEventStoreProjectionTest
{
    /**
     * @var InMemoryProjectionManager
     */
    protected $projectionManager;

    /**
     * @var InMemoryEventStore
     */
    protected $eventStore;

    protected function setUp(): void
    {
        $this->eventStore = new InMemoryEventStore();
        $this->projectionManager = new InMemoryProjectionManager($this->eventStore);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_trying_to_run_two_projections_at_the_same_time(): void
    {
        $this->markTestSkipped('InMemoryProjectionManager cannot guard agains concurrent projections');
    }

    /**
     * @test
     */
    public function it_deletes_projection_during_run_when_it_was_deleted_from_outside(): void
    {
        $this->markTestSkipped('InMemoryProjectionManager cannot delete projections');
    }

    /**
     * @test
     */
    public function it_deletes_projection_before_start_when_it_was_deleted_from_outside(): void
    {
        $this->markTestSkipped('InMemoryProjectionManager cannot delete projections');
    }

    /**
     * @test
     */
    public function it_deletes_projection_incl_emitting_events_before_start_when_it_was_deleted_from_outside(): void
    {
        $this->markTestSkipped('InMemoryProjectionManager cannot delete projections');
    }

    /**
     * @test
     */
    public function it_deletes_projection_incl_emitted_events_during_run_when_it_was_deleted_from_outside(): void
    {
        $this->markTestSkipped('InMemoryProjectionManager cannot delete projections');
    }

    /**
     * @test
     */
    public function it_resets_projection_before_start_when_it_was_reset_from_outside(): void
    {
        $this->markTestSkipped('InMemoryProjectionManager cannot reset projections');
    }

    /**
     * @test
     */
    public function it_resets_projection_during_run_when_it_was_reset_from_outside(): void
    {
        $this->markTestSkipped('InMemoryProjectionManager cannot reset projections');
    }

    /**
     * @test
     */
    public function it_stops_when_projection_before_start_when_it_was_stopped_from_outside(): void
    {
        $this->markTestSkipped('InMemoryProjectionManager cannot stop projections');
    }

    /**
     * @test
     */
    public function it_stops_projection_during_run_when_it_was_stopped_from_outside(): void
    {
        $this->markTestSkipped('InMemoryProjectionManager cannot stop projections');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_unknown_event_store_instance_passed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $eventStore = $this->prophesize(EventStore::class);

        new InMemoryEventStoreProjection($eventStore->reveal(), 'test_projection', 10, 10, 2000);
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

        new InMemoryEventStoreProjection($wrappedEventStore->reveal(), 'test_projection', 1, 1);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_cache_size_given(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new InMemoryEventStoreProjection($this->eventStore, 'test_projection', -1, 1);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_sleep_given(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new InMemoryEventStoreProjection($this->eventStore, 'test_projection', 1, -1);
    }
}
