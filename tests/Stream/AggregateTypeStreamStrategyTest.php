<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 08/31/14 - 11:59 PM
 */

namespace ProophTest\EventStore\Stream;

use Prooph\Common\Messaging\DomainEvent;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Stream\AggregateTypeStreamStrategy;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use ProophTest\EventStore\Mock\User;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;
use ProophTest\EventStore\TestCase;
use Rhumsaa\Uuid\Uuid;

/**
 * Class AggregateTypeStreamStrategyTest
 *
 * @package ProophTest\EventStore\Stream
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

        $this->eventStore->create(new Stream(new StreamName('ProophTest\EventStore\Mock\User'), new \ArrayIterator()));

        $this->eventStore->commit();
    }

    /**
     * @test
     */
    public function it_uses_one_stream_per_aggregate_type()
    {
        $this->eventStore->beginTransaction();

        $user = User::create("John Doe", "doe@test.com");

        $aggregateType = AggregateType::fromAggregateRoot($user);

        $aggregateId = Uuid::uuid4()->toString();

        $streamEvents = [
            UserCreated::with(
                ['user_id' => $aggregateId],
                1
            )
        ];

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId, new \ArrayIterator($streamEvents), $user);

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

        $user = User::create("John Doe", "doe@test.com");

        $aggregateType = AggregateType::fromAggregateRoot($user);

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

        $stream = $this->eventStore->load(new StreamName($aggregateType->toString()));

        $count = 0;
        foreach ($stream->streamEvents() as $event) {
            $count++;
        }
        $stream->streamEvents()->rewind();
        $this->assertEquals(2, $count);

        //Create a second user

        $this->eventStore->beginTransaction();

        $user = User::create("John Doe", "doe@test.com");

        $aggregateType = AggregateType::fromAggregateRoot($user);

        $aggregateId = Uuid::uuid4()->toString();

        $streamEvents = [
            UserCreated::with(
                ['user_id' => $aggregateId],
                1
            )
        ];

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId, new \ArrayIterator($streamEvents), $user);

        $this->eventStore->commit();

        $stream = $this->eventStore->load(new StreamName($aggregateType->toString()));

        $count = 0;
        foreach ($stream->streamEvents() as $event) {
            $count++;
        }
        $stream->streamEvents()->rewind();
        $this->assertEquals(3, $count);
    }

    /**
     * @test
     */
    public function it_reads_stream_events_for_aggregate()
    {
        $this->eventStore->beginTransaction();

        $user1 = User::create("John Doe", "doe@test.com");

        $aggregateType = AggregateType::fromAggregateRoot($user1);

        $aggregateId1 = Uuid::uuid4()->toString();

        $streamEvents1 = [
            UserCreated::with(
                ['user_id' => $aggregateId1],
                1
            )
        ];

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId1, new \ArrayIterator($streamEvents1), $user1);

        $aggregateId2 = Uuid::uuid4()->toString();

        $user2 = User::create("John Doe", "doe@test.com");

        $aggregateType = AggregateType::fromAggregateRoot($user2);

        $streamEvents2 = [
            UsernameChanged::with(
                ['name' => 'John Doe'],
                2
            )
        ];

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId2, new \ArrayIterator($streamEvents2), $user2);

        $this->eventStore->commit();

        $streamEvents = $this->strategy->read($aggregateType, $aggregateId1);

        $count = 0;
        foreach ($streamEvents as $event) {
            $count++;
        }
        $streamEvents->rewind();
        $this->assertEquals(1, $count);

        $this->assertInstanceOf(DomainEvent::class, $streamEvents[0]);

        $this->assertEquals($aggregateId1, $streamEvents[0]->payload()['user_id']);

        $this->assertTrue($aggregateType->equals($this->strategy->getAggregateRootType($aggregateType, $streamEvents)));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_does_not_allow_to_create_a_new_stream_with_wrong_repository_aggregate_type()
    {
        $this->eventStore->beginTransaction();

        $user = User::create("John Doe", "doe@test.com");

        $aggregateType = AggregateType::fromString("Product");

        $aggregateId = Uuid::uuid4()->toString();

        $streamEvents = [
            UserCreated::with(
                ['user_id' => $aggregateId],
                1
            )
        ];

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId, new \ArrayIterator($streamEvents), $user);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_does_not_allow_to_add_stream_events_with_wrong_repository_aggregate_type()
    {
        $this->eventStore->beginTransaction();

        $user = User::create("John Doe", "doe@test.com");

        $aggregateType = AggregateType::fromAggregateRoot($user);

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

        $this->strategy->appendEvents(AggregateType::fromString("Product"), $aggregateId, new \ArrayIterator($streamEvents), $user);
    }

    /**
     * @test
     */
    public function it_builds_stream_name_from_stream_map()
    {
        $aggregateType = $this->prophesize(AggregateType::class);
        $aggregateType->toString()->willReturn('foo')->shouldBeCalledTimes(2);

        $strategy = new AggregateTypeStreamStrategy($this->eventStore, ['foo' => 'bar']);

        $strategy->read($aggregateType->reveal(), 'someId');
    }
}
