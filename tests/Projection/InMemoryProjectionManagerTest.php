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

use PHPUnit\Framework\TestCase;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\EventStoreDecorator;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Projection\InMemoryProjectionManager;
use Prooph\EventStore\Projection\ProjectionStatus;

class InMemoryProjectionManagerTest extends TestCase
{
    /**
     * @var InMemoryProjectionManager
     */
    private $projectionManager;

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
    public function it_fetches_projection_names(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $this->projectionManager->createProjection('user-' . $i);
        }

        for ($i = 0; $i < 20; $i++) {
            $this->projectionManager->createProjection(uniqid('rand'));
        }

        $this->assertCount(70, $this->projectionManager->fetchProjectionNames(null, false, 200, 0));
        $this->assertCount(0, $this->projectionManager->fetchProjectionNames(null, false, 200, 100));
        $this->assertCount(10, $this->projectionManager->fetchProjectionNames(null, false, 10, 0));
        $this->assertCount(10, $this->projectionManager->fetchProjectionNames(null, false, 10, 10));
        $this->assertCount(5, $this->projectionManager->fetchProjectionNames(null, false, 10, 65));

        for ($i = 50; $i < 70; $i++) {
            $this->assertStringStartsWith('rand', $this->projectionManager->fetchProjectionNames(null, false, 1, $i)[0]);
        }

        $this->assertCount(30, $this->projectionManager->fetchProjectionNames('ser-', true, 30, 0));
        $this->assertCount(0, $this->projectionManager->fetchProjectionNames('n-', true, 30, 0));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_fetching_projection_names_using_regex_and_no_filter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No regex pattern given');

        $this->projectionManager->fetchProjectionNames(null, true, 10, 0);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_fetching_projection_names_using_invalid_regex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regex pattern given');

        $this->projectionManager->fetchProjectionNames('invalid)', true, 10, 0);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_asked_for_unknown_projection_status(): void
    {
        $this->expectException(RuntimeException::class);

        $this->projectionManager->fetchProjectionStatus('unkown');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_asked_for_unknown_projection_stream_positions(): void
    {
        $this->expectException(RuntimeException::class);

        $this->projectionManager->fetchProjectionStreamPositions('unkown');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_asked_for_unknown_projection_state(): void
    {
        $this->expectException(RuntimeException::class);

        $this->projectionManager->fetchProjectionState('unkown');
    }

    /**
     * @test
     */
    public function it_fetches_projection_status(): void
    {
        $projection = $this->projectionManager->createProjection('test-projection');

        $this->assertSame(ProjectionStatus::IDLE(), $this->projectionManager->fetchProjectionStatus('test-projection'));
    }

    /**
     * @test
     */
    public function it_fetches_projection_stream_positions(): void
    {
        $projection = $this->projectionManager->createProjection('test-projection');

        $this->assertSame(null, $this->projectionManager->fetchProjectionStreamPositions('test-projection'));
    }

    /**
     * @test
     */
    public function it_fetches_projection_state(): void
    {
        $projection = $this->projectionManager->createProjection('test-projection');

        $this->assertSame([], $this->projectionManager->fetchProjectionState('test-projection'));
    }
}
