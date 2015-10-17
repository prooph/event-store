<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 09/01/14 - 00:07 AM
 */

namespace ProophTest\EventStore\Stream;

use Prooph\Common\Messaging\DomainEvent;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Stream\SingleStreamStrategy;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use ProophTest\EventStore\Mock\Product;
use ProophTest\EventStore\Mock\TestDomainEvent;
use ProophTest\EventStore\Mock\User;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;
use ProophTest\EventStore\TestCase;
use Rhumsaa\Uuid\Uuid;

/**
 * Class SingleStreamStrategyTest
 *
 * @package ProophTest\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class SingleStreamStrategyTest extends TestCase
{
    /**
     * @var SingleStreamStrategy
     */
    private $strategy;

    protected function setUp()
    {
        parent::setUp();

        $this->strategy = new SingleStreamStrategy($this->eventStore);

        $this->eventStore->beginTransaction();

        $this->eventStore->create(new Stream(new StreamName('event_stream'), new \ArrayIterator()));

        $this->eventStore->commit();
    }

    /**
     * @test
     */
    public function it_uses_one_stream_for_all_aggregates()
    {
        $this->eventStore->beginTransaction();

        $user = User::create("John Doe", "doe@test.com");

        $aggregateType = AggregateType::fromString('SuperUser');

        $aggregateId = Uuid::uuid4()->toString();

        $streamEvents = [
            UserCreated::with(
                ['user_id' => $aggregateId],
                1
            )
        ];

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId, new \ArrayIterator($streamEvents), $user);

        $this->eventStore->commit();

        $stream = $this->eventStore->load(new StreamName('event_stream'));

        $this->assertInstanceOf(Stream::class, $stream);

        $count = 0;
        foreach ($stream->streamEvents() as $event) {
            $count++;
        }
        $stream->streamEvents()->rewind();
        $this->assertEquals(1, $count);

        $events = $stream->streamEvents();

        $arType = $this->strategy->getAggregateRootType($aggregateType, $events);

        $this->assertEquals('ProophTest\EventStore\Mock\User', $arType->toString());
    }

    /**
     * @test
     */
    public function it_appends_events_to_event_stream()
    {
        $this->eventStore->beginTransaction();

        $user = User::create("John Doe", "doe@test.com");

        $aggregateType = AggregateType::fromString('SuperUser');

        $aggregateId = Uuid::uuid4()->toString();

        $streamEvents = [
            UserCreated::with(
                ['user_id' => $aggregateId],
                1
            )
        ];

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId, new \ArrayIterator($streamEvents), $user);

        $this->eventStore->commit();

        $this->eventStore->beginTransaction();

        $streamEvents = [
            UsernameChanged::with(
                ['name' => 'John Doe'],
                2
            )
        ];

        $this->strategy->appendEvents($aggregateType, $aggregateId, new \ArrayIterator($streamEvents), $user);

        $this->eventStore->commit();

        $stream = $this->eventStore->load(new StreamName('event_stream'));

        $count = 0;
        foreach ($stream->streamEvents() as $event) {
            $count++;
        }
        $stream->streamEvents()->rewind();
        $this->assertEquals(2, $count);

        //Create a second aggregate

        $this->eventStore->beginTransaction();

        $product = new Product();

        $aggregateType = AggregateType::fromString('Product');

        $aggregateId = Uuid::uuid4()->toString();

        $streamEvents = [
            UserCreated::with(
                ['user_id' => $aggregateId],
                1
            )
        ];

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId, new \ArrayIterator($streamEvents), $product);

        $this->eventStore->commit();

        $stream = $this->eventStore->load(new StreamName('event_stream'));

        $count = 0;
        foreach ($stream->streamEvents() as $event) {
            $count++;
        }
        $this->assertEquals(3, $count);
    }

    /**
     * @test
     */
    public function it_reads_stream_events_for_aggregate()
    {
        $this->eventStore->beginTransaction();

        $user = User::create("John Doe", "doe@test.com");

        $aggregateType = AggregateType::fromString('Object');

        $aggregateId1 = Uuid::uuid4()->toString();

        $streamEvents1 = [
            UserCreated::with(
                ['user_id' => $aggregateId1],
                1
            )
        ];

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId1, new \ArrayIterator($streamEvents1), $user);

        $product = new Product();

        $aggregateId2 = Uuid::uuid4()->toString();

        $streamEvents2 = [
            TestDomainEvent::with(
                ['product_id' => $aggregateId2],
                1
            )
        ];

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId2, new \ArrayIterator($streamEvents2), $product);

        $this->eventStore->commit();

        $streamEvents = $this->strategy->read($aggregateType, $aggregateId2);

        $count = 0;
        foreach ($streamEvents as $event) {
            $count++;
        }
        $streamEvents->rewind();
        $this->assertEquals(1, $count);

        $this->assertInstanceOf(DomainEvent::class, $streamEvents[0]);

        $this->assertEquals($aggregateId2, $streamEvents[0]->payload()['product_id']);

        $arType = $this->strategy->getAggregateRootType($aggregateType, $streamEvents);

        $this->assertEquals('ProophTest\EventStore\Mock\Product', $arType->toString());
    }

    /**
     * @test
     * @expectedException Prooph\EventStore\Exception\RuntimeException
     * @expectedExceptionMessage The aggregate type cannot be detected
     */
    public function it_throws_exception_when_aggregate_type_cannot_be_detected_on_get_aggregate_root_type()
    {
        $aggregateType = AggregateType::fromString('Object');

        $streamEvent = $this->prophesize(Message::class);
        $streamEvent->metadata()->willReturn(['foo' => 'bar'])->shouldBeCalled();

        $streamEvents = [$streamEvent->reveal()];

        $this->strategy->getAggregateRootType($aggregateType, new \ArrayIterator($streamEvents));
    }
}
