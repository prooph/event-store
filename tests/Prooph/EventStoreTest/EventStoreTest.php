<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.04.14 - 21:27
 */

namespace Prooph\EventStoreTest;

use Prooph\EventStore\Adapter\InMemoryAdapter;
use Prooph\EventStore\Configuration\Configuration;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\PersistenceEvent\PostCommitEvent;
use Prooph\EventStore\Stream\DomainEventMetadataWriter;
use Prooph\EventStore\Stream\EventId;
use Prooph\EventStore\Stream\EventName;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamEvent;
use Prooph\EventStore\Stream\StreamName;
use Prooph\EventStoreTest\Mock\TestDomainEvent;
use Prooph\EventStoreTest\Mock\UserCreated;
use Prooph\EventStoreTest\Mock\UsernameChanged;
use Zend\EventManager\Event;

/**
 * Class EventStoreTest
 *
 * @package Prooph\EventStoreTest
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class EventStoreTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_new_stream_and_records_the_stream_events()
    {
        $recordedEvents = array();

        $this->eventStore->getPersistenceEvents()->attach('commit.post', function (PostCommitEvent $event) use (&$recordedEvents) {
            foreach ($event->getRecordedEvents() as $recordedEvent) {
                $recordedEvents[] = $recordedEvent;
            }
        });

        $this->eventStore->beginTransaction();

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $stream = $this->eventStore->load(new StreamName('user'));

        $this->assertEquals('user', $stream->streamName()->toString());

        $this->assertEquals(1, count($stream->streamEvents()));

        $this->assertEquals(1, count($recordedEvents));
    }

    /**
     * @test
     */
    public function it_stops_stream_creation_when_listener_stops_propagation()
    {
        $recordedEvents = array();

        $this->eventStore->getPersistenceEvents()->attach('commit.post', function (PostCommitEvent $event) use (&$recordedEvents) {
            foreach ($event->getRecordedEvents() as $recordedEvent) {
                $recordedEvents[] = $recordedEvent;
            }
        });

        $this->eventStore->getPersistenceEvents()->attach('create.pre', function (Event $event) {
            $event->stopPropagation(true);
        });

        $this->eventStore->beginTransaction();

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->assertEquals(0, count($recordedEvents));
    }

    /**
     * @test
     */
    public function it_breaks_stream_creation_when_it_is_not_in_transaction()
    {
        $this->setExpectedException('RuntimeException');

        $this->eventStore->create($this->getTestStream());
    }

    /**
     * @test
     */
    public function it_appends_events_to_stream_and_records_them()
    {
        $recordedEvents = array();

        $this->eventStore->getPersistenceEvents()->attach('commit.post', function (PostCommitEvent $event) use (&$recordedEvents) {
            foreach ($event->getRecordedEvents() as $recordedEvent) {
                $recordedEvents[] = $recordedEvent;
            }
        });

        $this->eventStore->beginTransaction();

        $this->eventStore->create($this->getTestStream());

        $this->eventStore->commit();

        $secondStreamEvent = UsernameChanged::with(
            array('new_name' => 'John Doe'),
            2
        );

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo(new StreamName('user'), array($secondStreamEvent));

        $this->eventStore->commit();

        $this->assertEquals(2, count($recordedEvents));
    }

    /**
     * @test
     */
    public function it_does_not_append_events_when_listener_stops_propagation()
    {
        $recordedEvents = array();

        $this->eventStore->getPersistenceEvents()->attach('commit.post', function (PostCommitEvent $event) use (&$recordedEvents) {
            foreach ($event->getRecordedEvents() as $recordedEvent) {
                $recordedEvents[] = $recordedEvent;
            }
        });

        $this->eventStore->beginTransaction();

        $this->eventStore->create($this->getTestStream());

        $this->eventStore->commit();

        $this->eventStore->getPersistenceEvents()->attach('appendTo.pre', function (Event $event) {
            $event->stopPropagation(true);
        });

        $secondStreamEvent = UsernameChanged::with(
            array('new_name' => 'John Doe'),
            2
        );

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo(new StreamName('user'), array($secondStreamEvent));

        $this->eventStore->commit();

        $this->assertEquals(1, count($recordedEvents));
    }

    /**
     * @test
     */
    public function it_breaks_appending_events_when_it_is_not_in_active_transaction()
    {
        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->setExpectedException('RuntimeException');

        $this->eventStore->appendTo($stream->streamName(), $stream->streamEvents());
    }

    /**
     * @test
     */
    public function it_loads_events_by_matching_metadata()
    {
        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $streamEventWithMetadata = TestDomainEvent::with(
            array('name' => 'Alex', 'email' => 'contact@prooph.de'),
            2
        );

        DomainEventMetadataWriter::setMetadataKey($streamEventWithMetadata, 'snapshot', true);

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo($stream->streamName(), array($streamEventWithMetadata));

        $this->eventStore->commit();

        $loadedEvents = $this->eventStore->loadEventsByMetadataFrom($stream->streamName(), array('snapshot' => true));

        $this->assertEquals(1, count($loadedEvents));

        $this->assertTrue($loadedEvents[0]->metadata()['snapshot']);
    }

    /**
     * @test
     */
    public function it_loads_events_by_min_version()
    {
        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $streamEventVersion2 = UsernameChanged::with(
            array('new_name' => 'John Doe'),
            2
        );

        DomainEventMetadataWriter::setMetadataKey($streamEventVersion2, 'snapshot', true);

        $streamEventVersion3 = UsernameChanged::with(
            array('new_name' => 'John Doe'),
            3
        );

        DomainEventMetadataWriter::setMetadataKey($streamEventVersion3, 'snapshot', false);

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo($stream->streamName(), array($streamEventVersion2, $streamEventVersion3));

        $this->eventStore->commit();

        $loadedEventStream = $this->eventStore->load($stream->streamName(), 2);

        $this->assertEquals(2, count($loadedEventStream->streamEvents()));

        $this->assertTrue($loadedEventStream->streamEvents()[0]->metadata()['snapshot']);
        $this->assertFalse($loadedEventStream->streamEvents()[1]->metadata()['snapshot']);

        $loadedEvents = $this->eventStore->loadEventsByMetadataFrom($stream->streamName(), array(), 2);

        $this->assertEquals(2, count($loadedEvents));

        $this->assertTrue($loadedEventStream->streamEvents()[0]->metadata()['snapshot']);
        $this->assertFalse($loadedEventStream->streamEvents()[1]->metadata()['snapshot']);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_when_listener_stops_loading_events_and_does_not_provide_loaded_events()
    {
        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $streamEventWithMetadata = UsernameChanged::with(
            array('new_name' => 'John Doe'),
            2
        );

        DomainEventMetadataWriter::setMetadataKey($streamEventWithMetadata, 'snapshot', true);

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo($stream->streamName(), array($streamEventWithMetadata));

        $this->eventStore->commit();

        $this->eventStore->getPersistenceEvents()->attach('loadEventsByMetadataFrom.pre', function (Event $event) {
            $event->stopPropagation(true);
        });

        $loadedEvents = $this->eventStore->loadEventsByMetadataFrom($stream->streamName(), array('snapshot' => true));

        $this->assertEquals(0, count($loadedEvents));
    }

    /**
     * @test
     */
    public function it_returns_listener_events_when_listener_stops_loading_events_and_provide_loaded_events()
    {
        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $streamEventWithMetadata = UsernameChanged::with(
            array('new_name' => 'John Doe'),
            2
        );

        DomainEventMetadataWriter::setMetadataKey($streamEventWithMetadata, 'snapshot', true);

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo($stream->streamName(), array($streamEventWithMetadata));

        $this->eventStore->commit();

        $this->eventStore->getPersistenceEvents()->attach('loadEventsByMetadataFrom.pre', function (Event $event) {
            $streamEventWithMetadataButOtherUuid = UsernameChanged::with(
                array('new_name' => 'John Doe'),
                1
            );

            DomainEventMetadataWriter::setMetadataKey($streamEventWithMetadataButOtherUuid, 'snapshot', true);

            $event->setParam('streamEvents', array($streamEventWithMetadataButOtherUuid));
            $event->stopPropagation(true);
        });

        $loadedEvents = $this->eventStore->loadEventsByMetadataFrom($stream->streamName(), array('snapshot' => true));

        $this->assertEquals(1, count($loadedEvents));

        $this->assertNotEquals($streamEventWithMetadata->uuid()->toString(), $loadedEvents[0]->uuid()->toString());
    }

    /**
     * @test
     */
    public function it_breaks_loading_a_stream_when_listener_stops_propagation_but_does_not_provide_a_stream()
    {
        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->eventStore->getPersistenceEvents()->attach('load.pre', function (Event $event) {
            $event->stopPropagation(true);
        });

        $this->setExpectedException('Prooph\EventStore\Exception\StreamNotFoundException');

        $this->eventStore->load(new StreamName('user'));
    }

    /**
     * @test
     */
    public function it_breaks_loading_a_stream_when_listener_stops_propagation_and_provides_stream_with_wrong_name()
    {
        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->eventStore->getPersistenceEvents()->attach('load.pre', function (Event $event) {
            $event->setParam('stream', new Stream(new StreamName('EmptyStream'), array()));
            $event->stopPropagation(true);
        });

        $this->setExpectedException('Prooph\EventStore\Exception\StreamNotFoundException');

        $this->eventStore->load(new StreamName('user'));
    }

    /**
     * @test
     */
    public function it_uses_stream_provided_by_listener_when_listener_stops_propagation()
    {
        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->eventStore->getPersistenceEvents()->attach('load.pre', function (Event $event) {
            $event->setParam('stream', new Stream(new StreamName('user'), array()));
            $event->stopPropagation(true);
        });

        $emptyStream = $this->eventStore->load($stream->streamName());

        $this->assertEquals(0, count($emptyStream->streamEvents()));
    }

    /**
     * @return Stream
     */
    private function getTestStream()
    {
        $streamEvent = UserCreated::with(
            array('name' => 'Alex', 'email' => 'contact@prooph.de'),
            1
        );

        return new Stream(new StreamName('user'), array($streamEvent));
    }
}
 