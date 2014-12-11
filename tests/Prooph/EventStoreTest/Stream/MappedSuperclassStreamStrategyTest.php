<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 20.10.14 - 20:35
 */

namespace Prooph\EventStoreTest\Stream;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Stream\EventId;
use Prooph\EventStore\Stream\EventName;
use Prooph\EventStore\Stream\MappedSuperclassStreamStrategy;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamEvent;
use Prooph\EventStore\Stream\StreamName;
use Prooph\EventStoreTest\TestCase;
use Rhumsaa\Uuid\Uuid;

/**
 * Class MappedSuperclassStreamStrategyTest
 *
 * @package Prooph\EventStoreTest\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class MappedSuperclassStreamStrategyTest extends TestCase
{
    /**
     * @var MappedSuperclassStreamStrategy
     */
    private $strategy;

    protected function setUp()
    {
        parent::setUp();

        $this->strategy = new MappedSuperclassStreamStrategy($this->eventStore, new AggregateType('User'));

        $this->eventStore->beginTransaction();

        $this->eventStore->create(new Stream(new StreamName('User'), array()));

        $this->eventStore->commit();
    }

    /**
     * @test
     */
    public function it_uses_one_stream_for_all_aggregates_extending_the_super_class()
    {
        $this->eventStore->beginTransaction();

        $aggregateType = new AggregateType('Employee');

        $aggregateId = Uuid::uuid4()->toString();

        $streamEvents = array(
            new StreamEvent(
                EventId::generate(),
                new EventName('EmployeeHired'),
                array('user_id' => $aggregateId),
                1,
                new \DateTime()
            )
        );

        $this->strategy->add($aggregateType, $aggregateId, $streamEvents);

        $this->eventStore->commit();

        $stream = $this->eventStore->load(new StreamName('User'));

        $this->assertInstanceOf('Prooph\EventStore\Stream\Stream', $stream);

        $this->assertEquals(1, count($stream->streamEvents()));
    }

    /**
     * @test
     */
    public function it_appends_events_to_event_stream()
    {
        $this->eventStore->beginTransaction();

        $aggregateType = new AggregateType('Employee');

        $aggregateId = Uuid::uuid4()->toString();

        $streamEvents = array(
            new StreamEvent(
                EventId::generate(),
                new EventName('EmployeeHired'),
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

        $stream = $this->eventStore->load(new StreamName('User'));

        $this->assertEquals(2, count($stream->streamEvents()));

        //Create a second aggregate

        $this->eventStore->beginTransaction();

        $aggregateType = new AggregateType('Freelance');

        $aggregateId = Uuid::uuid4()->toString();

        $streamEvents = array(
            new StreamEvent(
                EventId::generate(),
                new EventName('FreelanceCharged'),
                array('user_id' => $aggregateId),
                1,
                new \DateTime()
            )
        );

        $this->strategy->add($aggregateType, $aggregateId, $streamEvents);

        $this->eventStore->commit();

        $stream = $this->eventStore->load(new StreamName('User'));

        $this->assertEquals(3, count($stream->streamEvents()));
    }

    /**
     * @test
     */
    public function it_reads_stream_events_for_aggregate_and_silently_changes_aggregate_type_to_subclass()
    {
        $this->eventStore->beginTransaction();

        $aggregateType = new AggregateType('Employee');

        $aggregateId1 = Uuid::uuid4()->toString();

        $streamEvents1 = array(
            new StreamEvent(
                EventId::generate(),
                new EventName('EmployeeHired'),
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
                new EventName('FreelanceCharged'),
                array('user_id' => $aggregateId2),
                1,
                new \DateTime()
            )
        );

        $aggregateType = new AggregateType('Freelance');

        $this->strategy->add($aggregateType, $aggregateId2, $streamEvents2);

        $this->eventStore->commit();

        $aggregateType = new AggregateType('User');

        $streamEvents = $this->strategy->read($aggregateType, $aggregateId1);

        $this->assertEquals(1, count($streamEvents));

        $this->assertInstanceOf('Prooph\EventStore\Stream\StreamEvent', $streamEvents[0]);

        $this->assertEquals($aggregateId1, $streamEvents[0]->payload()['user_id']);

        $this->assertEquals('Employee', $aggregateType->toString());
    }

    /**
     * @test
     */
    public function it_uses_the_aggregate_type_map_to_write_to_a_global_stream()
    {
        $personType = new AggregateType('Person');

        $strategyWithAggregateMap = new MappedSuperclassStreamStrategy(
            $this->eventStore,
            $personType, [
                $personType->toString() => 'global_event_stream'
            ]
        );

        $this->eventStore->beginTransaction();

        $this->eventStore->create(new Stream(new StreamName('global_event_stream'), array()));

        $this->eventStore->commit();

        $this->eventStore->beginTransaction();

        $employeeType = new AggregateType('Employee');

        $aggregateId1 = Uuid::uuid4()->toString();

        $streamEvents1 = array(
            new StreamEvent(
                EventId::generate(),
                new EventName('EmployeeHired'),
                array('user_id' => $aggregateId1),
                1,
                new \DateTime()
            )
        );

        $strategyWithAggregateMap->add($employeeType, $aggregateId1, $streamEvents1);

        $this->eventStore->commit();

        $streamEvents = $strategyWithAggregateMap->read($personType, $aggregateId1);

        $this->assertEquals(1, count($streamEvents));

        $this->assertInstanceOf('Prooph\EventStore\Stream\StreamEvent', $streamEvents[0]);

        $this->assertEquals($aggregateId1, $streamEvents[0]->payload()['user_id']);

        //Person became Employee after reading the stream
        $this->assertEquals('Employee', $personType->toString());
    }
}
 