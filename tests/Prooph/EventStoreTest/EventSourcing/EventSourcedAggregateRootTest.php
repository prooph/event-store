<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 18.04.14 - 00:03
 */

namespace Prooph\EventStoreTest\EventSourcing;

use Prooph\EventStoreTest\Mock\User;
use Prooph\EventStoreTest\TestCase;

/**
 * Class EventSourcedAggregateRootTest
 *
 * @package Prooph\EventStoreTest\EventSourcing
 * @author Alexander Miertsch <contact@prooph.de>
 */
class EventSourcedAggregateRootTest extends TestCase
{
    /**
     * @test
     */
    public function it_applies_event_by_calling_appropriate_event_handler()
    {
        $user = new User('John');

        $this->assertEquals('John', $user->name());

        $user->changeName('Max');

        $this->assertEquals('Max', $user->name());

        $pendingEvents = $user->accessPendingEvents();

        $this->assertEquals(2, count($pendingEvents));

        $userCreatedEvent = $pendingEvents[0];

        $this->assertEquals('John', $userCreatedEvent->name());
        $this->assertEquals(1, $userCreatedEvent->version());

        $userNameChangedEvent = $pendingEvents[1];

        $this->assertEquals('Max', $userNameChangedEvent->newUsername());
        $this->assertEquals(2, $userNameChangedEvent->version());
    }

    /**
     * @test
     */
    public function it_reconstructs_itself_from_history()
    {
        $user = new User('John');

        $this->assertEquals('John', $user->name());

        $user->changeName('Max');

        $historyEvents = $user->accessPendingEvents();

        $sameUser = User::fromHistory($user->id(), $historyEvents);

        $this->assertEquals($user->id(), $sameUser->id());
        $this->assertEquals($user->name(), $sameUser->name());
    }

    /**
     * @test
     */
    public function it_clears_pending_events_after_returning_them()
    {
        $user = new User('John');

        $pendingEvents = $user->accessPendingEvents();

        $this->assertEquals(1, count($pendingEvents));

        $pendingEvents = $user->accessPendingEvents();

        $this->assertEquals(0, count($pendingEvents));
    }
}
 