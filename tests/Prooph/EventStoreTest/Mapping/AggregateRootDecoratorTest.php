<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.04.14 - 21:45
 */

namespace Prooph\EventStoreTest\Mapping;

use Prooph\EventStore\Mapping\AggregateRootDecorator;
use Prooph\EventStoreTest\Mock\User;
use Prooph\EventStoreTest\TestCase;

/**
 * Class AggregateRootDecoratorTest
 *
 * @package Prooph\EventStoreTest\Mapping
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AggregateRootDecoratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_extracts_the_pending_events_from_given_aggregate_root()
    {
        $user = new User("Alex");

        $decorator = new AggregateRootDecorator();

        $pendingEvents = $decorator->extractPendingEvents($user);

        $this->assertEquals(1, count($pendingEvents));

        $userCreatedEvent = $pendingEvents[0];

        $this->assertInstanceOf('Prooph\EventStoreTest\Mock\UserCreated', $userCreatedEvent);
    }

    /**
     * @test
     */
    public function it_extracts_aggregate_id()
    {
        $user = new User("Alex");

        $decorator = new AggregateRootDecorator();

        $userId = $decorator->getAggregateId($user);

        $this->assertEquals($userId, $user->id());
    }

    /**
     * @test
     */
    public function it_reconstructs_aggregate_from_history()
    {
        $user = new User("Alex");

        $user->changeName("Alexander");

        $decorator = new AggregateRootDecorator();

        $refUser = new \ReflectionClass($user);

        $userPrototype = $refUser->newInstanceWithoutConstructor();

        $equalUser = $decorator->fromHistory($userPrototype, $user->id(), $user->accessPendingEvents());

        $this->assertInstanceOf('Prooph\EventStoreTest\Mock\User', $equalUser);
        $this->assertEquals($equalUser->id(), $user->id());
        $this->assertEquals($equalUser->name(), $user->name());
    }
}
 