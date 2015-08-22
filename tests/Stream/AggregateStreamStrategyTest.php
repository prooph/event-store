<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 31.08.14 - 23:37
 */

namespace Prooph\EventStoreTest\Stream;

use Prooph\Common\Messaging\DomainEvent;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Stream\AggregateStreamStrategy;
use Prooph\EventStore\Stream\StreamName;
use Prooph\EventStoreTest\Mock\User;
use Prooph\EventStoreTest\Mock\UserCreated;
use Prooph\EventStoreTest\Mock\UsernameChanged;
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

        $user = User::create("John Doe", "doe@test.com");

        $aggregateType = AggregateType::fromAggregateRoot($user);

        $aggregateId = Uuid::uuid4()->toString();

        $streamEvents = [
            UserCreated::with(
                ['user_id' => $aggregateId],
                1
            )
        ];

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId, $streamEvents, $user);

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

        $user = User::create("John Doe", "doe@test.com");

        $aggregateType = AggregateType::fromAggregateRoot($user);

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

        $stream = $this->eventStore->load(new StreamName($aggregateType->toString() . '-' . $aggregateId));

        $this->assertEquals(2, count($stream->streamEvents()));
    }

    /**
     * @test
     */
    public function it_reads_stream_events_for_aggregate()
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

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId, $streamEvents, $user);

        $this->eventStore->commit();

        $streamEvents = $this->strategy->read($aggregateType, $aggregateId);

        $this->assertEquals(1, count($streamEvents));

        $this->assertInstanceOf(DomainEvent::class, $streamEvents[0]);

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

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId, $streamEvents, $user);
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

        $this->strategy->addEventsForNewAggregateRoot($aggregateType, $aggregateId, $streamEvents, $user);

        $this->eventStore->commit();

        $this->eventStore->beginTransaction();

        $streamEvents = [
            UsernameChanged::with(
                ['name' => 'John Doe'],
                2
            )
        ];

        $this->strategy->appendEvents(AggregateType::fromString("Product"), $aggregateId, $streamEvents, $user);
    }
}
