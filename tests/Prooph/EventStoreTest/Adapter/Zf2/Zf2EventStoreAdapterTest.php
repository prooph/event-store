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

use Prooph\EventSourcing\Mapping\AggregateChangedEventHydrator;
use Prooph\EventStore\Adapter\Zf2\Zf2EventStoreAdapter;
use Prooph\EventStore\Stream\AggregateType;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use Prooph\EventStoreTest\Mock\User;
use Prooph\EventStoreTest\TestCase;
use ValueObjects\DateTime\DateTime;
use Zend\Db\Adapter\Adapter;

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

        $eventHydrator = new AggregateChangedEventHydrator();

        $streamEvents = $eventHydrator->toStreamEvents($pendingEvents);

        $stream = new Stream(new AggregateType(get_class($user)), new StreamName($user->id()), $streamEvents);

        $this->adapter->addToExistingStream($stream);

        $historyEventStream = $this->adapter->loadStream(new AggregateType(get_class($user)), new StreamName($user->id()));

        $historyEvents = $historyEventStream->streamEvents();

        $this->assertEquals(1, count($historyEvents));

        $userCreatedEvent = $historyEvents[0];

        $this->assertEquals('Prooph\EventStoreTest\Mock\UserCreated', $userCreatedEvent->eventName()->toString());

        $this->assertEquals($pendingEvents[0]->uuid(), $userCreatedEvent->eventId()->toString());
        $this->assertEquals(1, $userCreatedEvent->version());

        $payload = $userCreatedEvent->payload();

        $this->assertEquals($user->name(), $payload['name']);
        $this->assertTrue($pendingEvents[0]->occurredOn()->sameValueAs(DateTime::fromNativeDateTime($userCreatedEvent->occurredOn())));
    }

    /**
     * @test
     */
    public function it_removes_a_stream()
    {
        $this->adapter->createSchema(array('User'));

        $user = new User("Alex");

        $pendingEvents = $user->accessPendingEvents();

        $eventHydrator = new AggregateChangedEventHydrator();

        $streamEvents = $eventHydrator->toStreamEvents($pendingEvents);

        $stream = new Stream(new AggregateType(get_class($user)), new StreamName($user->id()), $streamEvents);

        $this->adapter->addToExistingStream($stream);

        $this->adapter->removeStream(new AggregateType(get_class($user)), new StreamName($user->id()));

        $historyStream = $this->adapter->loadStream(new AggregateType(get_class($user)), new StreamName($user->id()));

        $this->assertEquals(0, count($historyStream->streamEvents()));
    }

    /**
     * @test
     */
    public function it_can_be_constructed_with_existing_db_adapter()
    {
        $zendDbAdapter = new Adapter(array(
                'driver' => 'Pdo_Sqlite',
                'database' => ':memory:'
            )
        );

        $esAdapter = new Zf2EventStoreAdapter(array('zend_db_adapter' => $zendDbAdapter));

        $this->assertSame($zendDbAdapter, \PHPUnit_Framework_Assert::readAttribute($esAdapter, 'dbAdapter'));
    }
}
 