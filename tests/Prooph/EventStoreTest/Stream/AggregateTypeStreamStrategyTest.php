<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 31.08.14 - 23:59
 */

namespace Prooph\EventStoreTest\Stream;

use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Stream\AggregateTypeStreamStrategy;
use Prooph\EventStore\Stream\EventId;
use Prooph\EventStore\Stream\EventName;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamEvent;
use Prooph\EventStore\Stream\StreamName;
use Prooph\EventStoreTest\TestCase;
use Rhumsaa\Uuid\Uuid;

/**
 * Class AggregateTypeStreamStrategyTest
 *
 * @package Prooph\EventStoreTest\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AggregateTypeStreamStrategyTest extends TestCase
{
    /**
     * @var AggregateTypeStreamStrategy
     */
    private $strategy;

    protected function setUp()
    {
        parent::setUp();

        $this->strategy = new AggregateTypeStreamStrategy($this->eventStore);

        $this->eventStore->beginTransaction();

        $this->eventStore->create(new Stream(new StreamName('User'), array()));

        $this->eventStore->commit();
    }

    /**
     * @test
     */
    public function it_uses_one_stream_per_aggregate_type()
    {
        $this->eventStore->beginTransaction();

        $aggregateType = AggregateType::fromString('User');

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

        $stream = $this->eventStore->load(new StreamName($aggregateType->toString()));

        $this->assertInstanceOf('Prooph\EventStore\Stream\Stream', $stream);
    }

    /**
     * @test
     */
    public function it_appends_events_to_aggregate_type_stream()
    {
        $this->eventStore->beginTransaction();

        $aggregateType = AggregateType::fromString('User');

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

        $stream = $this->eventStore->load(new StreamName($aggregateType->toString()));

        $this->assertEquals(2, count($stream->streamEvents()));

        //Create a second user

        $this->eventStore->beginTransaction();

        $aggregateType = AggregateType::fromString('User');

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

        $stream = $this->eventStore->load(new StreamName($aggregateType->toString()));

        $this->assertEquals(3, count($stream->streamEvents()));
    }

    /**
     * @test
     */
    public function it_reads_stream_events_for_aggregate()
    {
        $this->eventStore->beginTransaction();

        $aggregateType = AggregateType::fromString('User');

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
                new EventName('UserCreated'),
                array('user_id' => $aggregateId2),
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
 