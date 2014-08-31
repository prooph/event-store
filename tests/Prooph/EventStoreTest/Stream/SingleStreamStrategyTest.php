<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 01.09.14 - 00:07
 */

namespace Prooph\EventStoreTest\Stream;

use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Stream\EventId;
use Prooph\EventStore\Stream\EventName;
use Prooph\EventStore\Stream\SingleStreamStrategy;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamEvent;
use Prooph\EventStore\Stream\StreamName;
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

        $this->eventStore->create(new Stream(new StreamName('event_stream'), array()));

        $this->eventStore->commit();
    }

    /**
     * @test
     */
    public function it_uses_one_stream_for_all_aggregates()
    {
        $this->eventStore->beginTransaction();

        $aggregateType = new AggregateType('User');

        $aggregateId = Uuid::uuid4()->toString();

        $streamEvents = array(
            new StreamEvent(
                EventId::generate(),
                new EventName('UserCreated'),
                array('user_id' => $aggregateId),
                1,
                new \DateTime()
            )
        );

        $this->strategy->add($aggregateType, $aggregateId, $streamEvents);

        $this->eventStore->commit();

        $stream = $this->eventStore->load(new StreamName('event_stream'));

        $this->assertInstanceOf('Prooph\EventStore\Stream\Stream', $stream);

        $this->assertEquals(1, count($stream->streamEvents()));
    }

    /**
     * @test
     */
    public function it_appends_events_to_event_stream()
    {
        $this->eventStore->beginTransaction();

        $aggregateType = new AggregateType('User');

        $aggregateId = Uuid::uuid4()->toString();

        $streamEvents = array(
            new StreamEvent(
                EventId::generate(),
                new EventName('UserCreated'),
                array('user_id' => $aggregateId),
                1,
                new \DateTime()
            )
        );

        $this->strategy->add($aggregateType, $aggregateId, $streamEvents);

        $this->eventStore->commit();

        $this->eventStore->beginTransaction();

        $streamEvents = array(
            new StreamEvent(
                EventId::generate(),
                new EventName('UsernameChanged'),
                array('name' => 'John Doe'),
                2,
                new \DateTime()
            )
        );

        $this->strategy->appendEvents($aggregateType, $aggregateId, $streamEvents);

        $this->eventStore->commit();

        $stream = $this->eventStore->load(new StreamName('event_stream'));

        $this->assertEquals(2, count($stream->streamEvents()));

        //Create a second aggregate

        $this->eventStore->beginTransaction();

        $aggregateType = new AggregateType('Product');

        $aggregateId = Uuid::uuid4()->toString();

        $streamEvents = array(
            new StreamEvent(
                EventId::generate(),
                new EventName('ProductCreated'),
                array('product_id' => $aggregateId),
                1,
                new \DateTime()
            )
        );

        $this->strategy->add($aggregateType, $aggregateId, $streamEvents);

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

        $aggregateType = new AggregateType('User');

        $aggregateId1 = Uuid::uuid4()->toString();

        $streamEvents1 = array(
            new StreamEvent(
                EventId::generate(),
                new EventName('UserCreated'),
                array('user_id' => $aggregateId1),
                1,
                new \DateTime()
            )
        );

        $this->strategy->add($aggregateType, $aggregateId1, $streamEvents1);

        $aggregateId2 = Uuid::uuid4()->toString();

        $streamEvents2 = array(
            new StreamEvent(
                EventId::generate(),
                new EventName('ProductCreated'),
                array('product_id' => $aggregateId2),
                1,
                new \DateTime()
            )
        );

        $this->strategy->add($aggregateType, $aggregateId2, $streamEvents2);

        $this->eventStore->commit();

        $streamEvents = $this->strategy->read($aggregateType, $aggregateId1);

        $this->assertEquals(1, count($streamEvents));

        $this->assertInstanceOf('Prooph\EventStore\Stream\StreamEvent', $streamEvents[0]);

        $this->assertEquals($aggregateId1, $streamEvents[0]->payload()['user_id']);
    }
}
 