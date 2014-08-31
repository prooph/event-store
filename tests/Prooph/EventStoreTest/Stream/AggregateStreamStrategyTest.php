<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 31.08.14 - 23:37
 */

namespace Prooph\EventStoreTest\Stream;

use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Stream\AggregateStreamStrategy;
use Prooph\EventStore\Stream\EventId;
use Prooph\EventStore\Stream\EventName;
use Prooph\EventStore\Stream\StreamEvent;
use Prooph\EventStore\Stream\StreamName;
use Prooph\EventStoreTest\TestCase;
use Rhumsaa\Uuid\Uuid;

/**
 * Class AggregateStreamStrategyTest
 *
 * @package Prooph\EventStoreTest\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AggregateStreamStrategyTest extends TestCase
{
    /**
     * @var AggregateStreamStrategy
     */
    private $strategy;

    protected function setUp()
    {
        parent::setUp();

        $this->strategy = new AggregateStreamStrategy($this->eventStore);
    }

    /**
     * @test
     */
    public function it_creates_a_new_stream_for_each_aggregate()
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

        $stream = $this->eventStore->load(new StreamName($aggregateType->toString() . '-' . $aggregateId));

        $this->assertInstanceOf('Prooph\EventStore\Stream\Stream', $stream);
    }

    /**
     * @test
     */
    public function it_appends_events_to_aggregate_stream()
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

        $stream = $this->eventStore->load(new StreamName($aggregateType->toString() . '-' . $aggregateId));

        $this->assertEquals(2, count($stream->streamEvents()));
    }

    /**
     * @test
     */
    public function it_reads_stream_events_for_aggregate()
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

        $streamEvents = $this->strategy->read($aggregateType, $aggregateId);

        $this->assertEquals(1, count($streamEvents));

        $this->assertInstanceOf('Prooph\EventStore\Stream\StreamEvent', $streamEvents[0]);
    }
}
 