<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.04.14 - 22:30
 */

namespace Prooph\EventStoreTest\Adapter\Zf2;

use Prooph\EventStore\Adapter\Zf2\Zf2EventStoreAdapter;
use Prooph\EventStoreTest\Mock\User;
use Prooph\EventStoreTest\TestCase;

/**
 * Class Zf2EventStoreAdapterTest
 *
 * @package Prooph\EventStoreTest\Adapter\Zf2
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class Zf2EventStoreAdapterTest extends TestCase
{
    /**
     * @var Zf2EventStoreAdapter
     */
    protected $adapter;

    protected function setUp()
    {
        $this->adapter = $this->getEventStoreAdapter();
    }

    /**
     * @test
     */
    public function it_creates_schema_stores_event_stream_and_fetches_the_stream_from_db()
    {
        $this->adapter->createSchema(array('User'));

        $user = new User("Alex");

        $pendingEvents = $user->accessPendingEvents();

        $this->adapter->addToStream(get_class($user), $user->id(), $pendingEvents);

        $historyEvents = $this->adapter->loadStream(get_class($user), $user->id());

        $this->assertEquals(1, count($historyEvents));

        /* @var $userCreatedEvent \Prooph\EventStoreTest\Mock\UserCreated */
        $userCreatedEvent = $historyEvents[0];

        $this->assertInstanceOf('Prooph\EventStoreTest\Mock\UserCreated', $userCreatedEvent);

        $this->assertEquals($pendingEvents[0]->uuid(), $userCreatedEvent->uuid());
        $this->assertEquals(1, $userCreatedEvent->version());
        $this->assertEquals($user->id(), $userCreatedEvent->aggregateId());
        $this->assertEquals($user->id(), $userCreatedEvent->userId());
        $this->assertEquals($user->name(), $userCreatedEvent->name());
        $this->assertTrue($pendingEvents[0]->occurredOn()->sameValueAs($userCreatedEvent->occurredOn()));
    }

    /**
     * @test
     */
    public function it_removes_a_stream()
    {
        $this->adapter->createSchema(array('User'));

        $user = new User("Alex");

        $pendingEvents = $user->accessPendingEvents();

        $this->adapter->addToStream(get_class($user), $user->id(), $pendingEvents);

        $this->adapter->removeStream(get_class($user), $user->id());

        $pendingEvents = $this->adapter->loadStream(get_class($user), $user->id());

        $this->assertEquals(0, count($pendingEvents));
    }
}
 