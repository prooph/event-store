<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 01.09.14 - 00:07
 */

namespace Prooph\EventStoreTest\Stream;

use Prooph\Common\Messaging\DomainEvent;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Stream\SingleStreamStrategy;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use Prooph\EventStoreTest\Mock\Product;
use Prooph\EventStoreTest\Mock\TestDomainEvent;
use Prooph\EventStoreTest\Mock\User;
use Prooph\EventStoreTest\Mock\UserCreated;
use Prooph\EventStoreTest\Mock\UsernameChanged;
use Prooph\EventStoreTest\TestCase;
use Rhumsaa\Uuid\Uuid;

/**
 * Class SingleStreamStrategyTest
 *
 * @package Prooph\EventStoreTest\Stream
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

        $this->eventStore->create(new Stream(new StreamName('event_stream'), []));

        $this->eventStore->commit();
    }

    /**
     * @test
     */
    public function it_uses_one_stream_for_all_aggregates()
    {
        $this->eventStore->beginTransaction();

        $user = new User("John Doe", "doe@test.com");

        $aggregateType = AggregateType::fromString('SuperUser');

        $aggregateId = Uuid::uuid4()->toString();

        $streamEvents = [
            UserCreated::with(
                ['user_id' => $aggregateId],
                1
            )
        ];

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId, $streamEvents, $user);

        $this->eventStore->commit();

        $stream = $this->eventStore->load(new StreamName('event_stream'));

        $this->assertInstanceOf(Stream::class, $stream);

        $this->assertEquals(1, count($stream->streamEvents()));

        $events = $stream->streamEvents();

        $arType = $this->strategy->getAggregateRootType($aggregateType, $events);

        $this->assertEquals('Prooph\EventStoreTest\Mock\User', $arType->toString());
    }

    /**
     * @test
     */
    public function it_appends_events_to_event_stream()
    {
        $this->eventStore->beginTransaction();

        $user = new User("John Doe", "doe@test.com");

        $aggregateType = AggregateType::fromString('SuperUser');

        $aggregateId = Uuid::uuid4()->toString();

        $streamEvents = [
            UserCreated::with(
                ['user_id' => $aggregateId],
                1
            )
        ];

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId, $streamEvents, $user);

        $this->eventStore->commit();

        $this->eventStore->beginTransaction();

        $streamEvents = [
            UsernameChanged::with(
                ['name' => 'John Doe'],
                2
            )
        ];

        $this->strategy->appendEvents($aggregateType, $aggregateId, $streamEvents, $user);

        $this->eventStore->commit();

        $stream = $this->eventStore->load(new StreamName('event_stream'));

        $this->assertEquals(2, count($stream->streamEvents()));

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

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId, $streamEvents, $product);

        $this->eventStore->commit();

        $stream = $this->eventStore->load(new StreamName('event_stream'));

        $this->assertEquals(3, count($stream->streamEvents()));
    }

    /**
     * @test
     */
    public function it_reads_stream_events_for_aggregate()
    {
        $this->eventStore->beginTransaction();

        $user = new User("John Doe", "doe@test.com");

        $aggregateType = AggregateType::fromString('Object');

        $aggregateId1 = Uuid::uuid4()->toString();

        $streamEvents1 = [
            UserCreated::with(
                ['user_id' => $aggregateId1],
                1
            )
        ];

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId1, $streamEvents1, $user);

        $product = new Product();

        $aggregateId2 = Uuid::uuid4()->toString();

        $streamEvents2 = [
            TestDomainEvent::with(
                ['product_id' => $aggregateId2],
                1
            )
        ];

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId2, $streamEvents2, $product);

        $this->eventStore->commit();

        $streamEvents = $this->strategy->read($aggregateType, $aggregateId2);

        $this->assertEquals(1, count($streamEvents));

        $this->assertInstanceOf(DomainEvent::class, $streamEvents[0]);

        $this->assertEquals($aggregateId2, $streamEvents[0]->payload()['product_id']);

        $arType = $this->strategy->getAggregateRootType($aggregateType, $streamEvents);

        $this->assertEquals('Prooph\EventStoreTest\Mock\Product', $arType->toString());
    }
}
