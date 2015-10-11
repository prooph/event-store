<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 31.08.14 - 22:09
 */

namespace Prooph\EventStoreTest\Aggregate;

use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\Aggregate\AggregateRepository;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Aggregate\ConfigurableAggregateTranslator;
use Prooph\EventStore\Aggregate\IdentityMap;
use Prooph\EventStore\Aggregate\InMemoryIdentityMap;
use Prooph\EventStore\Snapshot\Adapter\InMemoryAdapter;
use Prooph\EventStore\Snapshot\Snapshot;
use Prooph\EventStore\Snapshot\SnapshotStore;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use Prooph\EventStoreTest\Mock\User;
use Prooph\EventStoreTest\Mock\UserCreated;
use Prooph\EventStoreTest\Mock\UsernameChanged;
use Prooph\EventStoreTest\TestCase;

/**
 * Class AggregateRepositoryTest
 *
 * @package Prooph\EventStoreTest\Aggregate
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AggregateRepositoryTest extends TestCase
{
    /**
     * @var AggregateRepository
     */
    private $repository;

    /**
     * @var SnapshotStore
     */
    private $snapshotStore;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = new AggregateRepository(
            $this->eventStore,
            AggregateType::fromAggregateRootClass('Prooph\EventStoreTest\Mock\User'),
            new ConfigurableAggregateTranslator()
        );

        $this->eventStore->beginTransaction();

        $this->eventStore->create(new Stream(new StreamName('event_stream'), new \ArrayIterator()));

        $this->eventStore->commit();
    }


    /**
     * @test
     */
    public function it_adds_a_new_aggregate()
    {
        $this->eventStore->beginTransaction();

        $user = User::create('John Doe', 'contact@prooph.de');

        $this->repository->addAggregateRoot($user);

        $this->eventStore->commit();

        $this->repository->clearIdentityMap();

        $fetchedUser = $this->repository->getAggregateRoot(
            $user->getId()->toString()
        );

        $this->assertInstanceOf('Prooph\EventStoreTest\Mock\User', $fetchedUser);

        $this->assertNotSame($user, $fetchedUser);

        $this->assertEquals('John Doe', $fetchedUser->name());

        $this->assertEquals('contact@prooph.de', $fetchedUser->email());
    }

    /**
     * @test
     */
    public function it_tracks_changes_of_aggregate()
    {
        $this->eventStore->beginTransaction();

        $user = User::create('John Doe', 'contact@prooph.de');

        $this->repository->addAggregateRoot($user);

        $this->eventStore->commit();

        $this->eventStore->beginTransaction();

        $fetchedUser = $this->repository->getAggregateRoot(
            $user->getId()->toString()
        );

        $this->assertSame($user, $fetchedUser);

        $fetchedUser->changeName('Max Mustermann');

        $this->eventStore->commit();

        $this->repository->clearIdentityMap();

        $fetchedUser2 = $this->repository->getAggregateRoot(
            $user->getId()->toString()
        );

        $this->assertNotSame($fetchedUser, $fetchedUser2);

        $this->assertEquals('Max Mustermann', $fetchedUser2->name());
    }

    /**
     * @test
     * Test for https://github.com/prooph/event-store/issues/99
     */
    public function it_does_not_interfere_with_other_aggregate_roots_in_pending_events_index()
    {
        $this->eventStore->beginTransaction();

        $user = User::create('John Doe', 'contact@prooph.de');

        $this->repository->addAggregateRoot($user);

        $user2 = User::create('Max Mustermann', 'some@mail.com');

        $this->repository->addAggregateRoot($user2);

        $this->eventStore->commit();

        $this->eventStore->beginTransaction();

        //Fetch users from repository to simulate a normal program flow
        $user = $this->repository->getAggregateRoot($user->getId()->toString());
        $user2 = $this->repository->getAggregateRoot($user2->getId()->toString());

        $user->changeName('Daniel Doe');
        $user2->changeName('Jens Mustermann');

        $this->eventStore->commit();

        $fetchedUser1 = $this->repository->getAggregateRoot(
            $user->getId()->toString()
        );

        $fetchedUser2 = $this->repository->getAggregateRoot(
            $user2->getId()->toString()
        );

        $this->assertEquals('Daniel Doe', $fetchedUser1->name());
        $this->assertEquals('Jens Mustermann', $fetchedUser2->name());
    }

    /**
     * @test
     * @expectedException Prooph\EventStore\Aggregate\Exception\AggregateTypeException
     * @expectedExceptionMessage Aggregate root must be an object but type of string given
     */
    public function it_asserts_correct_aggregate_type()
    {
        $this->repository->addAggregateRoot('invalid');
    }

    /**
     * @test
     */
    public function it_returns_early_on_get_aggregate_root_when_there_are_no_stream_events()
    {
        $this->assertNull($this->repository->getAggregateRoot('something'));
    }

    /**
     * @test
     * @expectedException Prooph\EventStore\Aggregate\Exception\RuntimeException
     * @expectedExceptionMessage Identity map cannot be cleared. It currently contains pending events
     */
    public function it_does_not_allow_to_clear_identity_map_as_long_as_it_contains_pending_events()
    {
        $this->eventStore->beginTransaction();
        
        $user = User::create('John Doe', 'contact@prooph.de');

        $this->repository->addAggregateRoot($user);

        $this->repository->clearIdentityMap();
    }

    /**
     * @test
     */
    public function it_uses_snapshot_store()
    {
        $this->prepareSnapshotStoreAggregateRepository();

        $this->eventStore->beginTransaction();

        $user = User::create('John Doe', 'contact@prooph.de');

        $this->repository->addAggregateRoot($user);

        $this->eventStore->commit();

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $snapshot = new Snapshot(
            AggregateType::fromAggregateRootClass('Prooph\EventStoreTest\Mock\User'),
            $user->getId()->toString(),
            $user,
            1,
            $now
        );

        // short getter assertion
        $this->assertSame($now, $snapshot->createdAt());

        $this->snapshotStore->add($snapshot);

        $this->repository->clearIdentityMap();

        $loadedEvents = [];

        $this->eventStore->getActionEventEmitter()->attachListener(
            'loadEventsByMetadataFrom.post',
            function (ActionEvent $event) use (&$loadedEvents) {
                foreach ($event->getParam('streamEvents', []) as $streamEvent) {
                    $loadedEvents[] = $streamEvent;
                }
            }
        );

        $this->repository->getAggregateRoot(
            $user->getId()->toString()
        );

        $this->assertEmpty($loadedEvents);
    }

    /**
     * @test
     */
    public function it_uses_snapshot_store_while_snapshot_store_is_empty()
    {
        $this->prepareSnapshotStoreAggregateRepository();

        $this->eventStore->beginTransaction();

        $user = User::create('John Doe', 'contact@prooph.de');

        $this->repository->addAggregateRoot($user);

        $this->eventStore->commit();

        $this->repository->clearIdentityMap();

        $loadedEvents = [];

        $this->eventStore->getActionEventEmitter()->attachListener(
            'loadEventsByMetadataFrom.post',
            function (ActionEvent $event) use (&$loadedEvents) {
                foreach ($event->getParam('streamEvents', []) as $streamEvent) {
                    $loadedEvents[] = $streamEvent;
                }
            }
        );

        $this->repository->getAggregateRoot(
            $user->getId()->toString()
        );

        $this->assertCount(1, $loadedEvents);
        $this->assertInstanceOf(UserCreated::class, $loadedEvents[0]);
    }

    /**
     * @test
     */
    public function it_uses_snapshot_store_and_applies_pending_events()
    {
        $this->prepareSnapshotStoreAggregateRepository();

        $this->eventStore->beginTransaction();

        $user = User::create('John Doe', 'contact@prooph.de');

        $this->repository->addAggregateRoot($user);

        $this->eventStore->commit();

        $snapshot = new Snapshot(
            AggregateType::fromAggregateRootClass('Prooph\EventStoreTest\Mock\User'),
            $user->getId()->toString(),
            $user,
            1,
            new \DateTimeImmutable('now', new \DateTimeZone('UTC'))
        );

        $this->snapshotStore->add($snapshot);

        $this->repository->clearIdentityMap();

        $this->eventStore->beginTransaction();

        $fetchedUser = $this->repository->getAggregateRoot(
            $user->getId()->toString()
        );

        $fetchedUser->changeName('Max Mustermann');

        $this->eventStore->commit();

        $this->repository->clearIdentityMap();

        $loadedEvents = [];

        $this->eventStore->getActionEventEmitter()->attachListener(
            'loadEventsByMetadataFrom.post',
            function (ActionEvent $event) use (&$loadedEvents) {
                foreach ($event->getParam('streamEvents', []) as $streamEvent) {
                    $loadedEvents[] = $streamEvent;
                }
                $event->getParam('streamEvents')->rewind();
            }
        );

        $this->repository->getAggregateRoot(
            $user->getId()->toString()
        );

        $this->assertCount(1, $loadedEvents);
        $this->assertInstanceOf(UsernameChanged::class, $loadedEvents[0]);
    }

    protected function prepareSnapshotStoreAggregateRepository()
    {
        parent::setUp();

        $this->snapshotStore = new SnapshotStore(new InMemoryAdapter());

        $this->repository = new AggregateRepository(
            $this->eventStore,
            AggregateType::fromAggregateRootClass('Prooph\EventStoreTest\Mock\User'),
            new ConfigurableAggregateTranslator(),
            null,
            $this->snapshotStore
        );

        $this->eventStore->beginTransaction();

        $this->eventStore->create(new Stream(new StreamName('event_stream'), new \ArrayIterator()));

        $this->eventStore->commit();
    }
}
