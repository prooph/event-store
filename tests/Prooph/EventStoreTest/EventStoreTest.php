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

use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\PersistenceEvent\PostCommitEvent;
use Prooph\EventStore\PersistenceEvent\PreCommitEvent;
use Prooph\EventStore\Stream\DomainEventMetadataWriter;
use Prooph\EventStore\Stream\Stream;
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
        $recordedEvents = [];

        $this->eventStore->getActionEventDispatcher()->attachListener('commit.post', function (PostCommitEvent $event) use (&$recordedEvents) {
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
    public function it_allows_nested_transactions_but_triggers_commit_post_event_only_once()
    {
        $postCommitEventCount = 0;

        $this->eventStore->getActionEventDispatcher()->attachListener('commit.post', function (PostCommitEvent $event) use (&$postCommitEventCount) {
            $postCommitEventCount++;
        });

        $this->eventStore->beginTransaction();

        $this->eventStore->beginTransaction();

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->eventStore->commit();

        $this->assertEquals(1, $postCommitEventCount);
    }

    /**
     * @test
     */
    public function it_triggers_commit_pre_event_for_nested_transaction_too()
    {
        $preCommitEventCount = 0;

        $this->eventStore->getActionEventDispatcher()->attachListener('commit.pre', function (PreCommitEvent $event) use (&$preCommitEventCount) {
            $preCommitEventCount++;
        });

        $this->eventStore->beginTransaction();

        $this->eventStore->beginTransaction();

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->eventStore->commit();

        $this->assertEquals(2, $preCommitEventCount);
    }

    /**
     * @test
     */
    public function it_adds_information_about_transaction_level_to_commit_pre_event()
    {
        $transactionLevelOfNestedTransaction = null;
        $transactionFlagOfNestedTransaction = null;
        $transactionLevelOfMainTransaction = null;
        $transactionFlagOfMainTransaction = null;
        $triggerCount = 0;

        $this->eventStore->getActionEventDispatcher()->attachListener(
            'commit.pre',
            function (PreCommitEvent $event) use (
                &$transactionLevelOfNestedTransaction, &$transactionFlagOfNestedTransaction, &$triggerCount,
                &$transactionLevelOfMainTransaction, &$transactionFlagOfMainTransaction
            ) {
                $triggerCount++;

                if ($triggerCount === 2) {
                    $transactionLevelOfMainTransaction = $event->getParam('transactionLevel');
                    $transactionFlagOfMainTransaction  = $event->getParam('isNestedTransaction');
                } else {
                    $transactionLevelOfNestedTransaction = $event->getParam('transactionLevel');
                    $transactionFlagOfNestedTransaction  = $event->getParam('isNestedTransaction');
                }
            }
        );

        $this->eventStore->beginTransaction();

        $this->eventStore->beginTransaction();

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->eventStore->commit();

        $this->assertEquals(1, $transactionLevelOfMainTransaction);
        $this->assertFalse($transactionFlagOfMainTransaction);
        $this->assertEquals(2, $transactionLevelOfNestedTransaction);
        $this->assertTrue($transactionFlagOfNestedTransaction);
    }

    /**
     * @test
     */
    public function it_adds_information_about_transaction_level_to_begin_transaction_event_and_triggers_the_event_on_every_call()
    {
        $transactionLevelOfNestedTransaction = null;
        $transactionFlagOfNestedTransaction = null;
        $transactionLevelOfMainTransaction = null;
        $transactionFlagOfMainTransaction = null;
        $triggerCount = 0;

        $this->eventStore->getActionEventDispatcher()->attachListener(
            'beginTransaction',
            function (ActionEvent $event) use (
                &$transactionLevelOfNestedTransaction, &$transactionFlagOfNestedTransaction, &$triggerCount,
                &$transactionLevelOfMainTransaction, &$transactionFlagOfMainTransaction
            ) {
                $triggerCount++;

                if ($triggerCount === 1) {
                    $transactionLevelOfMainTransaction = $event->getParam('transactionLevel');
                    $transactionFlagOfMainTransaction  = $event->getParam('isNestedTransaction');
                } else {
                    $transactionLevelOfNestedTransaction = $event->getParam('transactionLevel');
                    $transactionFlagOfNestedTransaction  = $event->getParam('isNestedTransaction');
                }
            }
        );

        $this->eventStore->beginTransaction();

        $this->eventStore->beginTransaction();

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->eventStore->commit();

        $this->assertEquals(1, $transactionLevelOfMainTransaction);
        $this->assertFalse($transactionFlagOfMainTransaction);
        $this->assertEquals(2, $transactionLevelOfNestedTransaction);
        $this->assertTrue($transactionFlagOfNestedTransaction);
    }

    /**
     * @test
     */
    public function it_stops_stream_creation_when_listener_stops_propagation()
    {
        $recordedEvents = [];

        $this->eventStore->getActionEventDispatcher()->attachListener('commit.post', function (PostCommitEvent $event) use (&$recordedEvents) {
            foreach ($event->getRecordedEvents() as $recordedEvent) {
                $recordedEvents[] = $recordedEvent;
            }
        });

        $this->eventStore->getActionEventDispatcher()->attachListener('create.pre', function (ActionEvent $event) {
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
        $recordedEvents = [];

        $this->eventStore->getActionEventDispatcher()->attachListener('commit.post', function (PostCommitEvent $event) use (&$recordedEvents) {
            foreach ($event->getRecordedEvents() as $recordedEvent) {
                $recordedEvents[] = $recordedEvent;
            }
        });

        $this->eventStore->beginTransaction();

        $this->eventStore->create($this->getTestStream());

        $this->eventStore->commit();

        $secondStreamEvent = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo(new StreamName('user'), [$secondStreamEvent]);

        $this->eventStore->commit();

        $this->assertEquals(2, count($recordedEvents));
    }

    /**
     * @test
     */
    public function it_does_not_append_events_when_listener_stops_propagation()
    {
        $recordedEvents = [];

        $this->eventStore->getActionEventDispatcher()->attachListener('commit.post', function (PostCommitEvent $event) use (&$recordedEvents) {
            foreach ($event->getRecordedEvents() as $recordedEvent) {
                $recordedEvents[] = $recordedEvent;
            }
        });

        $this->eventStore->beginTransaction();

        $this->eventStore->create($this->getTestStream());

        $this->eventStore->commit();

        $this->eventStore->getActionEventDispatcher()->attachListener('appendTo.pre', function (Event $event) {
            $event->stopPropagation(true);
        });

        $secondStreamEvent = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo(new StreamName('user'), [$secondStreamEvent]);

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
            ['name' => 'Alex', 'email' => 'contact@prooph.de'],
            2
        );

        DomainEventMetadataWriter::setMetadataKey($streamEventWithMetadata, 'snapshot', true);

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo($stream->streamName(), [$streamEventWithMetadata]);

        $this->eventStore->commit();

        $loadedEvents = $this->eventStore->loadEventsByMetadataFrom($stream->streamName(), ['snapshot' => true]);

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
            ['new_name' => 'John Doe'],
            2
        );

        DomainEventMetadataWriter::setMetadataKey($streamEventVersion2, 'snapshot', true);

        $streamEventVersion3 = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            3
        );

        DomainEventMetadataWriter::setMetadataKey($streamEventVersion3, 'snapshot', false);

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo($stream->streamName(), [$streamEventVersion2, $streamEventVersion3]);

        $this->eventStore->commit();

        $loadedEventStream = $this->eventStore->load($stream->streamName(), 2);

        $this->assertEquals(2, count($loadedEventStream->streamEvents()));

        $this->assertTrue($loadedEventStream->streamEvents()[0]->metadata()['snapshot']);
        $this->assertFalse($loadedEventStream->streamEvents()[1]->metadata()['snapshot']);

        $loadedEvents = $this->eventStore->loadEventsByMetadataFrom($stream->streamName(), [], 2);

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
            ['new_name' => 'John Doe'],
            2
        );

        DomainEventMetadataWriter::setMetadataKey($streamEventWithMetadata, 'snapshot', true);

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo($stream->streamName(), [$streamEventWithMetadata]);

        $this->eventStore->commit();

        $this->eventStore->getActionEventDispatcher()->attachListener('loadEventsByMetadataFrom.pre', function (ActionEvent $event) {
            $event->stopPropagation(true);
        });

        $loadedEvents = $this->eventStore->loadEventsByMetadataFrom($stream->streamName(), ['snapshot' => true]);

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
            ['new_name' => 'John Doe'],
            2
        );

        DomainEventMetadataWriter::setMetadataKey($streamEventWithMetadata, 'snapshot', true);

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo($stream->streamName(), [$streamEventWithMetadata]);

        $this->eventStore->commit();

        $this->eventStore->getActionEventDispatcher()->attachListener('loadEventsByMetadataFrom.pre', function (ActionEvent $event) {
            $streamEventWithMetadataButOtherUuid = UsernameChanged::with(
                ['new_name' => 'John Doe'],
                1
            );

            DomainEventMetadataWriter::setMetadataKey($streamEventWithMetadataButOtherUuid, 'snapshot', true);

            $event->setParam('streamEvents', [$streamEventWithMetadataButOtherUuid]);
            $event->stopPropagation(true);
        });

        $loadedEvents = $this->eventStore->loadEventsByMetadataFrom($stream->streamName(), ['snapshot' => true]);

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

        $this->eventStore->getActionEventDispatcher()->attachListener('load.pre', function (ActionEvent $event) {
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

        $this->eventStore->getActionEventDispatcher()->attachListener('load.pre', function (Event $event) {
            $event->setParam('stream', new Stream(new StreamName('EmptyStream'), []));
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

        $this->eventStore->getActionEventDispatcher()->attachListener('load.pre', function (ActionEvent $event) {
            $event->setParam('stream', new Stream(new StreamName('user'), []));
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
            ['name' => 'Alex', 'email' => 'contact@prooph.de'],
            1
        );

        return new Stream(new StreamName('user'), [$streamEvent]);
    }
}
