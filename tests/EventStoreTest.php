<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore;

use ArrayIterator;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\Adapter\Adapter;
use Prooph\EventStore\Adapter\Feature\CanHandleTransaction;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Exception\StreamNotFoundException;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use ProophTest\EventStore\Mock\TransactionalInMemoryAdapterMock;
use ProophTest\EventStore\Mock\PostCreated;
use ProophTest\EventStore\Mock\TestDomainEvent;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;
use Prophecy\Argument;

/**
 * Class EventStoreTest
 *
 * @package ProophTest\EventStore
 * @author Alexander Miertsch <contact@prooph.de>
 */
class EventStoreTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_new_stream_and_records_the_stream_events(): void
    {
        $recordedEvents = [];

        $this->eventStore->getActionEventEmitter()->attachListener('commit.post', function (ActionEvent $event) use (&$recordedEvents) {
            foreach ($event->getParam('recordedEvents', new \ArrayIterator()) as $recordedEvent) {
                $recordedEvents[] = $recordedEvent;
            }
        });

        $this->eventStore->beginTransaction();

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $this->assertEquals(1, count($this->eventStore->getRecordedEvents()));

        $this->eventStore->commit();

        $stream = $this->eventStore->load(new StreamName('user'));

        $this->assertEquals('user', $stream->streamName()->toString());

        $this->assertEquals(1, count($stream->streamEvents()));

        $this->assertEquals(1, count($recordedEvents));
    }

    /**
     * @test
     */
    public function it_stops_stream_creation_when_listener_stops_propagation(): void
    {
        $recordedEvents = [];

        $this->eventStore->getActionEventEmitter()->attachListener('commit.post', function (ActionEvent $event) use (&$recordedEvents) {
            foreach ($event->getParam('recordedEvents', new \ArrayIterator()) as $recordedEvent) {
                $recordedEvents[] = $recordedEvent;
            }
        });

        $this->eventStore->getActionEventEmitter()->attachListener('create.pre', function (ActionEvent $event) {
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
    public function it_breaks_stream_creation_when_it_is_not_in_transaction(): void
    {
        $this->setExpectedException('RuntimeException');

        $this->eventStore->create($this->getTestStream());
    }

    /**
     * @test
     */
    public function it_appends_events_to_stream_and_records_them(): void
    {
        $recordedEvents = [];

        $this->eventStore->getActionEventEmitter()->attachListener('commit.post', function (ActionEvent $event) use (&$recordedEvents) {
            foreach ($event->getParam('recordedEvents', new \ArrayIterator()) as $recordedEvent) {
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

        $this->eventStore->appendTo(new StreamName('user'), new ArrayIterator([$secondStreamEvent]));

        $this->eventStore->commit();

        $this->assertEquals(2, count($recordedEvents));
    }

    /**
     * @test
     */
    public function it_does_not_append_events_when_listener_stops_propagation(): void
    {
        $recordedEvents = [];

        $this->eventStore->getActionEventEmitter()->attachListener('commit.post', function (ActionEvent $event) use (&$recordedEvents) {
            foreach ($event->getParam('recordedEvents', new \ArrayIterator()) as $recordedEvent) {
                $recordedEvents[] = $recordedEvent;
            }
        });

        $this->eventStore->beginTransaction();

        $this->eventStore->create($this->getTestStream());

        $this->eventStore->commit();

        $this->eventStore->getActionEventEmitter()->attachListener('appendTo.pre', function (ActionEvent $event) {
            $event->stopPropagation(true);
        });

        $secondStreamEvent = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo(new StreamName('user'), new ArrayIterator([$secondStreamEvent]));

        $this->eventStore->commit();

        $this->assertEquals(1, count($recordedEvents));
    }

    /**
     * @test
     */
    public function it_breaks_appending_events_when_it_is_not_in_active_transaction(): void
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
    public function it_loads_events_by_matching_metadata(): void
    {
        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $streamEventWithMetadata = TestDomainEvent::with(
            ['name' => 'Alex', 'email' => 'contact@prooph.de'],
            2
        );

        $streamEventWithMetadata = $streamEventWithMetadata->withAddedMetadata('snapshot', true);

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo($stream->streamName(), new ArrayIterator([$streamEventWithMetadata]));

        $this->eventStore->commit();

        $loadedEvents = $this->eventStore->loadEventsByMetadataFrom($stream->streamName(), ['snapshot' => true]);

        $this->assertEquals(1, count($loadedEvents));

        $this->assertTrue($loadedEvents[0]->metadata()['snapshot']);
    }

    /**
     * @test
     */
    public function it_loads_events_by_min_version(): void
    {
        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $streamEventVersion2 = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $streamEventVersion2 = $streamEventVersion2->withAddedMetadata('snapshot', true);

        $streamEventVersion3 = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            3
        );

        $streamEventVersion3 = $streamEventVersion3->withAddedMetadata('snapshot', false);

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo($stream->streamName(), new ArrayIterator([$streamEventVersion2, $streamEventVersion3]));

        $this->eventStore->commit();

        $loadedEventStream = $this->eventStore->load($stream->streamName(), 2);

        $count = 0;
        foreach ($loadedEventStream->streamEvents() as $event) {
            $count++;
        }
        $loadedEventStream->streamEvents()->rewind();
        $this->assertEquals(2, $count);

        $this->assertTrue($loadedEventStream->streamEvents()[0]->metadata()['snapshot']);
        $this->assertFalse($loadedEventStream->streamEvents()[1]->metadata()['snapshot']);

        $loadedEvents = $this->eventStore->loadEventsByMetadataFrom($stream->streamName(), [], 2);

        $count = 0;
        foreach ($loadedEvents as $event) {
            $count++;
        }
        $loadedEvents->rewind();
        $this->assertEquals(2, $count);

        $this->assertTrue($loadedEventStream->streamEvents()[0]->metadata()['snapshot']);
        $this->assertFalse($loadedEventStream->streamEvents()[1]->metadata()['snapshot']);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_when_listener_stops_loading_events_and_does_not_provide_loaded_events(): void
    {
        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $streamEventWithMetadata = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $streamEventWithMetadata = $streamEventWithMetadata->withAddedMetadata('snapshot', true);

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo($stream->streamName(), new ArrayIterator([$streamEventWithMetadata]));

        $this->eventStore->commit();

        $this->eventStore->getActionEventEmitter()->attachListener('loadEventsByMetadataFrom.pre', function (ActionEvent $event) {
            $event->stopPropagation(true);
        });

        $loadedEvents = $this->eventStore->loadEventsByMetadataFrom($stream->streamName(), ['snapshot' => true]);

        $this->assertEquals(0, count($loadedEvents));
    }

    /**
     * @test
     */
    public function it_returns_listener_events_when_listener_stops_loading_events_and_provide_loaded_events(): void
    {
        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $streamEventWithMetadata = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $streamEventWithMetadata = $streamEventWithMetadata->withAddedMetadata('snapshot', true);

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo($stream->streamName(), new ArrayIterator([$streamEventWithMetadata]));

        $this->eventStore->commit();

        $this->eventStore->getActionEventEmitter()->attachListener('loadEventsByMetadataFrom.pre', function (ActionEvent $event) {
            $streamEventWithMetadataButOtherUuid = UsernameChanged::with(
                ['new_name' => 'John Doe'],
                1
            );

            $streamEventWithMetadataButOtherUuid = $streamEventWithMetadataButOtherUuid->withAddedMetadata('snapshot', true);

            $event->setParam('streamEvents', new ArrayIterator([$streamEventWithMetadataButOtherUuid]));
            $event->stopPropagation(true);
        });

        $loadedEvents = $this->eventStore->loadEventsByMetadataFrom($stream->streamName(), ['snapshot' => true]);

        $count = 0;
        foreach ($loadedEvents as $event) {
            $count++;
        }
        $this->assertEquals(1, $count);

        $loadedEvents->rewind();

        $this->assertNotEquals($streamEventWithMetadata->uuid()->toString(), $loadedEvents->current()->uuid()->toString());
    }

    /**
     * @test
     */
    public function it_breaks_loading_a_stream_when_listener_stops_propagation_but_does_not_provide_a_stream(): void
    {
        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->eventStore->getActionEventEmitter()->attachListener('load.pre', function (ActionEvent $event) {
            $event->stopPropagation(true);
        });

        $this->setExpectedException('Prooph\EventStore\Exception\StreamNotFoundException');

        $this->eventStore->load(new StreamName('user'));
    }

    /**
     * @test
     */
    public function it_breaks_loading_a_stream_when_listener_stops_propagation_and_provides_stream_with_wrong_name(): void
    {
        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->eventStore->getActionEventEmitter()->attachListener('load.pre', function (ActionEvent $event) {
            $event->setParam('stream', new Stream(new StreamName('EmptyStream'), new ArrayIterator()));
            $event->stopPropagation(true);
        });

        $this->setExpectedException('Prooph\EventStore\Exception\StreamNotFoundException');

        $this->eventStore->load(new StreamName('user'));
    }

    /**
     * @test
     */
    public function it_uses_stream_provided_by_listener_when_listener_stops_propagation(): void
    {
        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->eventStore->getActionEventEmitter()->attachListener('load.pre', function (ActionEvent $event) {
            $event->setParam('stream', new Stream(new StreamName('user'), new ArrayIterator()));
            $event->stopPropagation(true);
        });

        $emptyStream = $this->eventStore->load($stream->streamName());

        $count = 0;
        foreach ($emptyStream as $event) {
            $count++;
        }

        $this->assertEquals(0, $count);
    }

    /**
     * @test
     */
    public function it_throws_stream_not_found_exception_if_adapter_loads_nothing(): void
    {
        $this->expectException(StreamNotFoundException::class);

        $stream = $this->getTestStream();

        $adapter = $this->prophesize(Adapter::class);

        $eventStore = new EventStore($adapter->reveal(), new ProophActionEventEmitter());

        $eventStore->beginTransaction();

        $eventStore->create($stream);

        $eventStore->commit();

        $eventStore->load($stream->streamName());
    }

    /**
     * @test
     */
    public function it_throws_stream_not_found_exception_if_event_propagation_is_stopped_on_load_post(): void
    {
        $this->expectException(StreamNotFoundException::class);

        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->eventStore->getActionEventEmitter()->attachListener('load.post', function (ActionEvent $event) {
            $event->stopPropagation(true);
        });

        $this->eventStore->load($stream->streamName());
    }

    /**
     * @test
     */
    public function it_throws_stream_not_found_exception_if_event_propagation_is_stopped_on_load_event_by_metadata_from_post(): void
    {
        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->eventStore->getActionEventEmitter()->attachListener('loadEventsByMetadataFrom.post', function (ActionEvent $event) {
            $event->stopPropagation(true);
        });

        $this->assertEmpty($this->eventStore->loadEventsByMetadataFrom($stream->streamName(), []));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_trying_to_commit_transaction_without_open_transation(): void
    {
        $this->expectException(RuntimeException::class);

        $this->eventStore->commit();
    }

    /**
     * @test
     */
    public function it_throws_exception_when_trying_to_rollback_transaction_without_open_transation(): void
    {
        $this->expectException(RuntimeException::class);

        $this->eventStore->rollback();
    }

    /**
     * @test
     */
    public function it_cannot_rollback_transaction_if_adapter_cannot_handle_transaction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Adapter cannot handle transaction and therefore cannot rollback');

        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->rollback();

        $this->assertEmpty($this->eventStore->load($stream->streamName()));
    }

    /**
     * @test
     */
    public function it_can_rollback_transaction(): void
    {
        $this->expectException(StreamNotFoundException::class);

        $stream = $this->getTestStream();

        $adapter = $this->prophesize(Adapter::class);
        $adapter->willImplement(CanHandleTransaction::class);

        $this->eventStore = new EventStore($adapter->reveal(), new ProophActionEventEmitter());

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->rollback();

        $this->eventStore->load($stream->streamName());
    }

    /**
     * @test
     */
    public function it_makes_rollback_when_event_is_stopped_during_commit(): void
    {
        $this->expectException(StreamNotFoundException::class);

        $stream = $this->getTestStream();

        $adapter = $this->prophesize(Adapter::class);
        $adapter->willImplement(CanHandleTransaction::class);

        $this->eventStore = new EventStore($adapter->reveal(), new ProophActionEventEmitter());

        $this->eventStore->getActionEventEmitter()->attachListener('commit.pre', function (ActionEvent $event) {
            $event->stopPropagation(true);
        });

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->eventStore->load($stream->streamName());
    }

    /**
     * @test
     */
    public function it_should_rollback_and_throw_exception_in_case_of_transaction_fail(): void
    {
        $eventStore = new EventStore(new TransactionalInMemoryAdapterMock(), new ProophActionEventEmitter());

        $this->setExpectedException(\Exception::class, 'Transaction failed');

        $eventStore->transactional(function (EventStore $es) use ($eventStore) {
            self::assertSame($es, $eventStore);

            throw new \Exception('Transaction failed');
        });
    }

    /**
     * @test
     */
    public function it_should_return_true_by_default_if_transaction_is_used(): void
    {
        $transactionResult = $this->eventStore->transactional(function (EventStore $eventStore) {
            $this->eventStore->create($this->getTestStream());

            self::assertSame($this->eventStore, $eventStore);
        });

        self::assertTrue($transactionResult);
    }

    /**
     * @test
     */
    public function it_wraps_up_code_in_transaction_properly(): void
    {
        $recordedEvents = [];

        $this->eventStore->getActionEventEmitter()->attachListener('commit.post', function (ActionEvent $event) use (&$recordedEvents) {
            foreach ($event->getParam('recordedEvents', new \ArrayIterator()) as $recordedEvent) {
                $recordedEvents[] = $recordedEvent;
            }
        });

        $transactionResult = $this->eventStore->transactional(function (EventStore $eventStore) {
            $this->eventStore->create($this->getTestStream());

            self::assertSame($this->eventStore, $eventStore);

            return 'Result';
        });

        self::assertSame('Result', $transactionResult);

        $secondStreamEvent = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $transactionResult = $this->eventStore->transactional(function (EventStore $eventStore) use ($secondStreamEvent) {
            $this->eventStore->appendTo(new StreamName('user'), new ArrayIterator([$secondStreamEvent]));

            self::assertSame($this->eventStore, $eventStore);

            return 'Second Result';
        });

        self::assertSame('Second Result', $transactionResult);
        self::assertCount(2, $recordedEvents);
    }

    /**
     * @test
     */
    public function it_commits_transaction_if_adapter_implements_can_handle_transaction(): void
    {
        $stream = $this->getTestStream();

        $adapter = $this->prophesize(Adapter::class);
        $adapter->willImplement(CanHandleTransaction::class);

        $adapter->beginTransaction()->shouldBeCalled();
        $adapter->create($stream)->shouldBeCalled();
        $adapter->commit()->shouldBeCalled();
        $adapter->load(Argument::any(), null)->willReturn($stream);

        $this->eventStore = new EventStore($adapter->reveal(), new ProophActionEventEmitter());

        $this->eventStore->beginTransaction();

        $this->assertTrue($this->eventStore->isInTransaction());

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->assertFalse($this->eventStore->isInTransaction());

        $stream = $this->eventStore->load($stream->streamName());

        $this->assertEquals('user', $stream->streamName()->toString());

        $count = 0;
        foreach ($stream->streamEvents() as $event) {
            $count++;
        }
        $this->assertEquals(1, $count);
    }

    /**
     * @test
     */
    public function it_replays_in_correct_order(): void
    {
        $streamEvent1 = UserCreated::with(
            ['name' => 'Alex', 'email' => 'contact@prooph.de'],
            1
        );

        $streamEvent2 = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $streamEvent3 = PostCreated::with(
            ['text' => 'some text'],
            1
        );

        $stream1 = new Stream(new StreamName('user'), new ArrayIterator([$streamEvent1]));
        $stream2 = new Stream(new StreamName('post'), new ArrayIterator([$streamEvent3]));

        $this->eventStore->beginTransaction();
        $this->eventStore->create($stream1);
        $this->eventStore->commit();

        $this->eventStore->beginTransaction();
        $this->eventStore->create($stream2);
        $this->eventStore->commit();

        $this->eventStore->beginTransaction();
        $this->eventStore->appendTo(new StreamName('user'), new ArrayIterator([$streamEvent2]));
        $this->eventStore->commit();

        $iterator = $this->eventStore->replay([new StreamName('user'), new StreamName('post')]);

        $count = 0;
        foreach ($iterator as $key => $event) {
            $count += 1;
            if (1 === $count) {
                $this->assertInstanceOf(UserCreated::class, $event);
            }
            if (2 === $count) {
                $this->assertInstanceOf(UsernameChanged::class, $event);
            }
            if (3 === $count) {
                $this->assertInstanceOf(PostCreated::class, $event);
            }
        }
        $this->assertEquals(3, $count);
    }

    /**
     * @test
     */
    public function it_replays_since_specific_date(): void
    {
        $streamEvent1 = UserCreated::withPayloadAndSpecifiedCreatedAt(
            ['name' => 'Alex', 'email' => 'contact@prooph.de'],
            1,
            new \DateTimeImmutable('2 seconds ago')
        );

        $stream1 = new Stream(new StreamName('user'), new ArrayIterator([$streamEvent1]));

        $this->eventStore->beginTransaction();
        $this->eventStore->create($stream1);
        $this->eventStore->commit();

        $now = new \DateTime('now');

        $streamEvent2 = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $streamEvent3 = PostCreated::with(
            ['text' => 'some text'],
            1
        );

        $stream2 = new Stream(new StreamName('post'), new ArrayIterator([$streamEvent3]));

        $this->eventStore->beginTransaction();
        $this->eventStore->create($stream2);
        $this->eventStore->commit();

        $this->eventStore->beginTransaction();
        $this->eventStore->appendTo(new StreamName('user'), new ArrayIterator([$streamEvent2]));
        $this->eventStore->commit();

        $iterator = $this->eventStore->replay([new StreamName('user'), new StreamName('post')], $now);

        $count = 0;
        foreach ($iterator as $key => $event) {
            $count += 1;
            if (1 === $count) {
                $this->assertInstanceOf(UsernameChanged::class, $event);
            }
            if (2 === $count) {
                $this->assertInstanceOf(PostCreated::class, $event);
            }
        }
        $this->assertEquals(2, $count);
    }

    /**
     * @test
     */
    public function it_replays_in_correct_order_with_same_date_time(): void
    {
        $sameDate = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $streamEvent1 = UserCreated::withPayloadAndSpecifiedCreatedAt(
            ['name' => 'Alex', 'email' => 'contact@prooph.de'],
            1,
            $sameDate
        );

        $streamEvent2 = UsernameChanged::withPayloadAndSpecifiedCreatedAt(
            ['new_name' => 'John Doe'],
            2,
            $sameDate
        );

        $streamEvent3 = PostCreated::withPayloadAndSpecifiedCreatedAt(
            ['text' => 'some text'],
            1,
            $sameDate
        );

        $stream1 = new Stream(new StreamName('user'), new ArrayIterator([$streamEvent1]));
        $stream2 = new Stream(new StreamName('post'), new ArrayIterator([$streamEvent3]));

        $this->eventStore->beginTransaction();
        $this->eventStore->create($stream1);
        $this->eventStore->commit();

        $this->eventStore->beginTransaction();
        $this->eventStore->create($stream2);
        $this->eventStore->commit();

        $this->eventStore->beginTransaction();
        $this->eventStore->appendTo(new StreamName('user'), new ArrayIterator([$streamEvent2]));
        $this->eventStore->commit();

        $iterator = $this->eventStore->replay([new StreamName('user'), new StreamName('post')]);

        $count = 0;
        foreach ($iterator as $key => $event) {
            $count += 1;
            if (1 === $count) {
                $this->assertInstanceOf(UserCreated::class, $event);
            }
            if (2 === $count) {
                $this->assertInstanceOf(UsernameChanged::class, $event);
            }
            if (3 === $count) {
                $this->assertInstanceOf(PostCreated::class, $event);
            }
        }
        $this->assertEquals(3, $count);
    }

    /**
     * @test
     */
    public function it_rejects_replay_without_stream_names(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No stream names given');

        $this->eventStore->replay([], null, []);
    }

    /**
     * @test
     */
    public function it_expects_matching_of_stream_names_and_metadata(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('One metadata per stream name needed, given 2 stream names but 1 metadatas');

        $this->eventStore->replay([new StreamName('user'), new StreamName('post')], null, [[]]);
    }

    private function getTestStream(): Stream
    {
        $streamEvent = UserCreated::with(
            ['name' => 'Alex', 'email' => 'contact@prooph.de'],
            1
        );

        return new Stream(new StreamName('user'), new ArrayIterator([$streamEvent]));
    }
}
