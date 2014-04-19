<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.04.14 - 22:48
 */

namespace Prooph\EventStoreTest\Repository;

use Prooph\EventStoreTest\Mock\User;
use Prooph\EventStoreTest\TestCase;

/**
 * Class EventSourcingRepositoryTest
 *
 * @package Prooph\EventStoreTest\Repository
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class EventSourcingRepositoryTest extends TestCase
{
    protected function setUp()
    {
        $this->getTestEventStore()->getAdapter()->createSchema(array('User'));
    }

    protected function tearDown()
    {
        $this->getTestEventStore()->getAdapter()->dropSchema(array('User'));
        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_adds_aggregate_to_event_store_and_returns_it()
    {
        $this->getTestEventStore()->beginTransaction();

        $repository = $this->getTestEventStore()->getRepository('Prooph\EventStoreTest\Mock\User');

        $user = new User("Alex");

        $repository->add($user);

        $this->getTestEventStore()->commit();

        $this->getTestEventStore()->clear();

        $equalUser = $repository->get($user->id());

        $this->assertInstanceOf('Prooph\EventStoreTest\Mock\User', $equalUser);

        $this->assertNotSame($user, $equalUser);

        $this->assertEquals($user->id(), $equalUser->id());

        $this->assertEquals('Alex', $equalUser->name());
    }

    /**
     * @test
     */
    public function it_removes_an_aggregate()
    {
        $this->getTestEventStore()->beginTransaction();

        $repository = $this->getTestEventStore()->getRepository('Prooph\EventStoreTest\Mock\User');

        $user = new User("Alex");

        $repository->add($user);

        $repository->remove($user);

        $this->assertNull($repository->get($user->id()));
    }
}
 