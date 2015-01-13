<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 31.08.14 - 22:09
 */

namespace Prooph\EventStoreTest\Aggregate;

use Prooph\EventStore\Aggregate\AggregateRepository;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Aggregate\DefaultAggregateTranslator;
use Prooph\EventStore\Stream\SingleStreamStrategy;
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
            new DefaultAggregateTranslator(),
            new SingleStreamStrategy($this->eventStore),
            AggregateType::fromAggregateRootClass('Prooph\EventStoreTest\Mock\User')
        );

        $this->eventStore->beginTransaction();

        $this->eventStore->create(new Stream(new StreamName('event_stream'), array()));

        $this->eventStore->commit();
    }


    /**
     * @test
     */
    public function it_adds_a_new_aggregate()
    {
        $this->eventStore->beginTransaction();

        $user = new User('John Doe', 'contact@prooph.de');

        $this->repository->addAggregateRoot($user);

        $this->eventStore->commit();

        $this->clearRepositoryIdentityMap();

        $fetchedUser = $this->repository->getAggregateRoot(
            $user->id()->toString()
        );

        $this->assertInstanceOf('Prooph\EventStoreTest\Mock\User', $user);

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

        $user = new User('John Doe', 'contact@prooph.de');

        $this->repository->addAggregateRoot($user);

        $this->eventStore->commit();

        $this->eventStore->beginTransaction();

        $fetchedUser = $this->repository->getAggregateRoot(
            $user->id()->toString()
        );

        $this->assertSame($user, $fetchedUser);

        $fetchedUser->changeName('Max Mustermann');

        $this->eventStore->commit();

        $this->clearRepositoryIdentityMap();

        $fetchedUser2 = $this->repository->getAggregateRoot(
            $user->id()->toString()
        );

        $this->assertNotSame($fetchedUser, $fetchedUser2);

        $this->assertEquals('Max Mustermann', $fetchedUser2->name());
    }

    protected function clearRepositoryIdentityMap()
    {
        $refClass = new \ReflectionClass($this->repository);

        $identityMap = $refClass->getProperty('identityMap');

        $identityMap->setAccessible(true);

        $identityMap->setValue($this->repository, array());
    }
}
 