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

use Prooph\EventStore\Aggregate\AggregateRepository;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Aggregate\ConfigurableAggregateTranslator;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use Prooph\EventStoreTest\Mock\User;
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

        $this->clearRepositoryIdentityMap();

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

        $this->clearRepositoryIdentityMap();

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
     * @expectedExceptionMessage Invalid aggregate given. Aggregates need to be of type object but type of string given
     */
    public function it_throws_exception_when_added_aggregate_root_is_not_an_object()
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

    protected function clearRepositoryIdentityMap()
    {
        $refClass = new \ReflectionClass($this->repository);

        $identityMap = $refClass->getProperty('identityMap');

        $identityMap->setAccessible(true);

        $identityMap->setValue($this->repository, []);
    }
}
