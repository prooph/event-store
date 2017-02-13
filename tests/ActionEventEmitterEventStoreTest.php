<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore;

use ArrayIterator;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\ConcurrencyException;
use Prooph\EventStore\Exception\StreamExistsAlready;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Metadata\Operator;
use Prooph\EventStore\Projection\Projection;
use Prooph\EventStore\Projection\Query;
use Prooph\EventStore\Projection\ReadModelProjection;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\EventStore\Mock\ReadModelMock;
use ProophTest\EventStore\Mock\UsernameChanged;

class ActionEventEmitterEventStoreTest extends ActionEventEmitterEventStoreTestCase
{
    /**
     * @test
     */
    public function it_breaks_loading_a_stream_when_listener_stops_propagation_but_does_not_provide_a_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $this->eventStore->attach(
            'load',
            function (ActionEvent $event): void {
                $event->stopPropagation(true);
            },
            1000
        );

        $this->eventStore->load(new StreamName('user'));
    }

    /**
     * @test
     */
    public function it_cannot_create_a_stream_with_same_name_twice(): void
    {
        $this->expectException(StreamExistsAlready::class);

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);
        $this->eventStore->create($stream);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_trying_to_append_to_non_existing_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $streamName = $this->prophesize(StreamName::class);
        $streamName->toString()->willReturn('test');

        $this->eventStore->appendTo($streamName->reveal(), new \ArrayIterator());
    }

    /**
     * @test
     */
    public function it_throws_concurrency_exception_when_it_happens(): void
    {
        $this->expectException(ConcurrencyException::class);

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        // does cannot happen at InMemoryEventStore, so we test this with a listener

        $this->eventStore->attach(
            ActionEventEmitterEventStore::EVENT_APPEND_TO,
            function (ActionEvent $event) {
                $event->setParam('concurrencyException', true);
            },
            1000
        );

        $this->eventStore->appendTo($stream->streamName(), new ArrayIterator());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_trying_to_load_non_existing_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $streamName = $this->prophesize(StreamName::class);
        $streamName->toString()->willReturn('test');

        $this->assertNull($this->eventStore->load($streamName->reveal()));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_trying_to_load_reverse_non_existing_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $streamName = $this->prophesize(StreamName::class);
        $streamName->toString()->willReturn('test');

        $this->assertNull($this->eventStore->loadReverse($streamName->reveal()));
    }

    /**
     * @test
     */
    public function it_loads_events_in_reverse_order(): void
    {
        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $streamEventVersion2 = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $streamEventVersion2 = $streamEventVersion2->withAddedMetadata('snapshot', true);

        $streamEventVersion3 = UsernameChanged::with(
            ['new_name' => 'Jane Doe'],
            3
        );

        $streamEventVersion3 = $streamEventVersion3->withAddedMetadata('snapshot', false);

        $streamEventVersion4 = UsernameChanged::with(
            ['new_name' => 'Jane Dole'],
            4
        );

        $streamEventVersion4 = $streamEventVersion4->withAddedMetadata('snapshot', false);

        $this->eventStore->appendTo($stream->streamName(), new ArrayIterator([
            $streamEventVersion2,
            $streamEventVersion3,
            $streamEventVersion4,
        ]));

        $loadedEvents = $this->eventStore->loadReverse($stream->streamName(), 3, 2);

        $this->assertCount(2, $loadedEvents);

        $loadedEvents->rewind();

        $this->assertFalse($loadedEvents->current()->metadata()['snapshot']);
        $loadedEvents->next();
        $this->assertTrue($loadedEvents->current()->metadata()['snapshot']);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_listener_stops_loading_events_and_does_not_provide_loaded_events(): void
    {
        $this->expectException(StreamNotFound::class);

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $this->eventStore->attach(
            'load',
            function (ActionEvent $event): void {
                $event->stopPropagation(true);
            },
            1000
        );

        $this->eventStore->load($stream->streamName());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_listener_stops_loading_events_and_does_not_provide_loaded_events_reverse(): void
    {
        $this->expectException(StreamNotFound::class);

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $this->eventStore->attach(
            'loadReverse',
            function (ActionEvent $event): void {
                $event->stopPropagation(true);
            },
            1000
        );

        $this->eventStore->loadReverse($stream->streamName());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_trying_to_delete_unknown_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $streamName = $this->prophesize(StreamName::class);
        $streamName->toString()->willReturn('foo')->shouldBeCalled();

        $this->eventStore->delete($streamName->reveal());
    }

    /**
     * @test
     */
    public function it_does_not_append_events_when_listener_stops_propagation(): void
    {
        $recordedEvents = [];

        $this->eventStore->attach(
            'create',
            function (ActionEvent $event) use (&$recordedEvents): void {
                foreach ($event->getParam('recordedEvents', new \ArrayIterator()) as $recordedEvent) {
                    $recordedEvents[] = $recordedEvent;
                }
            },
            -1000
        );

        $this->eventStore->attach(
            'appendTo',
            function (ActionEvent $event) use (&$recordedEvents): void {
                foreach ($event->getParam('recordedEvents', new \ArrayIterator()) as $recordedEvent) {
                    $recordedEvents[] = $recordedEvent;
                }
            },
            -1000
        );

        $this->eventStore->create($this->getTestStream());

        $this->eventStore->attach(
            'appendTo',
            function (ActionEvent $event): void {
                $event->stopPropagation(true);
            },
            1000
        );

        $secondStreamEvent = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $this->eventStore->appendTo(new StreamName('user'), new ArrayIterator([$secondStreamEvent]));

        $this->assertCount(1, $this->eventStore->load(new StreamName('user')));
    }

    /**
     * @test
     */
    public function it_uses_stream_provided_by_listener_when_listener_stops_propagation(): void
    {
        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $this->eventStore->attach(
            'load',
            function (ActionEvent $event): void {
                $event->setParam('streamEvents', new ArrayIterator());
                $event->stopPropagation(true);
            },
            1000
        );

        $emptyStream = $this->eventStore->load($stream->streamName());

        $this->assertCount(0, $emptyStream);
    }

    /**
     * @test
     */
    public function it_returns_listener_events_when_listener_stops_loading_events_and_provide_loaded_events(): void
    {
        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $streamEventWithMetadata = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $streamEventWithMetadata = $streamEventWithMetadata->withAddedMetadata('snapshot', true);

        $this->eventStore->appendTo($stream->streamName(), new ArrayIterator([$streamEventWithMetadata]));

        $this->eventStore->attach(
            'load',
            function (ActionEvent $event): void {
                $streamEventWithMetadataButOtherUuid = UsernameChanged::with(
                    ['new_name' => 'John Doe'],
                    2
                );

                $streamEventWithMetadataButOtherUuid = $streamEventWithMetadataButOtherUuid->withAddedMetadata('snapshot', true);

                $event->setParam('streamEvents', new ArrayIterator([$streamEventWithMetadataButOtherUuid]));
                $event->stopPropagation(true);
            },
            1000
        );

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('snapshot', Operator::EQUALS(), true);

        $loadedEvents = $this->eventStore->load($stream->streamName(), 1, null, $metadataMatcher);

        $this->assertCount(1, $loadedEvents);

        $loadedEvents->rewind();

        $this->assertNotEquals($streamEventWithMetadata->uuid()->toString(), $loadedEvents->current()->uuid()->toString());
    }

    /**
     * @test
     */
    public function it_appends_events_to_stream_and_records_them(): void
    {
        $recordedEvents = [];

        $this->eventStore->attach(
            'create',
            function (ActionEvent $event) use (&$recordedEvents): void {
                $stream = $event->getParam('stream');

                foreach ($stream->streamEvents() as $recordedEvent) {
                    $recordedEvents[] = $recordedEvent;
                }
            },
            -1000
        );

        $this->eventStore->attach(
            'appendTo',
            function (ActionEvent $event) use (&$recordedEvents): void {
                foreach ($event->getParam('streamEvents', new \ArrayIterator()) as $recordedEvent) {
                    $recordedEvents[] = $recordedEvent;
                }
            },
            -1000
        );

        $this->eventStore->create($this->getTestStream());

        $secondStreamEvent = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $this->eventStore->appendTo(new StreamName('user'), new ArrayIterator([$secondStreamEvent]));

        $this->assertCount(2, $recordedEvents);
    }

    /**
     * @test
     */
    public function it_creates_a_new_stream_and_records_the_stream_events_and_deletes(): void
    {
        $recordedEvents = [];

        $streamName = new StreamName('user');

        $this->eventStore->attach(
            'create',
            function (ActionEvent $event) use (&$recordedEvents): void {
                $stream = $event->getParam('stream');

                foreach ($stream->streamEvents() as $recordedEvent) {
                    $recordedEvents[] = $recordedEvent;
                }
            },
            -1000
        );

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $streamEvents = $this->eventStore->load($streamName);

        $this->assertCount(1, $streamEvents);

        $this->assertCount(1, $recordedEvents);

        $this->assertEquals(
            [
                'foo' => 'bar',
            ],
            $this->eventStore->fetchStreamMetadata($streamName)
        );

        $this->assertTrue($this->eventStore->hasStream($streamName));

        $this->eventStore->delete($streamName);

        $this->assertFalse($this->eventStore->hasStream($streamName));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_asked_for_unknown_stream_metadata(): void
    {
        $this->expectException(StreamNotFound::class);

        $streamName = $this->prophesize(StreamName::class);
        $streamName->toString()->willReturn('unknown')->shouldBeCalled();

        $this->eventStore->fetchStreamMetadata($streamName->reveal());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_asked_for_stream_metadata_and_event_gets_stopped(): void
    {
        $this->expectException(StreamNotFound::class);

        $streamName = $this->prophesize(StreamName::class);
        $streamName->toString()->willReturn('test')->shouldBeCalled();

        $this->eventStore->attach(
            ActionEventEmitterEventStore::EVENT_FETCH_STREAM_METADATA,
            function (ActionEvent $event) {
                $event->stopPropagation();
            },
            1000
        );

        $this->eventStore->fetchStreamMetadata($streamName->reveal());
    }

    /**
     * @test
     */
    public function it_updates_stream_metadata(): void
    {
        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $this->eventStore->updateStreamMetadata($stream->streamName(), ['new' => 'values']);

        $this->assertEquals(
            [
                'new' => 'values',
            ],
            $this->eventStore->fetchStreamMetadata($stream->streamName())
        );
    }

    /**
     * @test
     */
    public function it_throws_stream_not_found_exception_when_trying_to_update_metadata_on_unknown_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $this->eventStore->updateStreamMetadata(new StreamName('unknown'), []);
    }

    /**
     * @test
     */
    public function it_creates_quey(): void
    {
        $this->assertInstanceOf(Query::class, $this->eventStore->createQuery());
    }

    /**
     * @test
     */
    public function it_creates_projection(): void
    {
        $this->assertInstanceOf(Projection::class, $this->eventStore->createProjection('foo'));
    }

    /**
     * @test
     */
    public function it_creates_read_model_projection(): void
    {
        $readModel = new ReadModelMock();

        $this->assertInstanceOf(ReadModelProjection::class, $this->eventStore->createReadModelProjection('foo', $readModel));
    }

    /**
     * @test
     */
    public function it_deletes_projections(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->deleteProjection('foo', true)->shouldBeCalled();

        $wrapper = new ActionEventEmitterEventStore($eventStore->reveal(), new ProophActionEventEmitter());

        $wrapper->deleteProjection('foo', true);
    }

    /**
     * @test
     */
    public function it_resets_projections(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->resetProjection('foo')->shouldBeCalled();

        $wrapper = new ActionEventEmitterEventStore($eventStore->reveal(), new ProophActionEventEmitter());

        $wrapper->resetProjection('foo');
    }

    /**
     * @test
     */
    public function it_stops_projections(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->stopProjection('foo')->shouldBeCalled();

        $wrapper = new ActionEventEmitterEventStore($eventStore->reveal(), new ProophActionEventEmitter());

        $wrapper->stopProjection('foo');
    }

    /**
     * @test
     */
    public function it_fetches_stream_names(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->fetchStreamNames('foo', false, null, 10, 20)->shouldBeCalled();

        $wrapper = new ActionEventEmitterEventStore($eventStore->reveal(), new ProophActionEventEmitter());

        $wrapper->fetchStreamNames('foo', false, null, 10, 20);
    }

    /**
     * @test
     */
    public function it_fetches_category_names(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->fetchCategoryNames('foo', false, 10, 20)->shouldBeCalled();

        $wrapper = new ActionEventEmitterEventStore($eventStore->reveal(), new ProophActionEventEmitter());

        $wrapper->fetchCategoryNames('foo', false, 10, 20);
    }

    /**
     * @test
     */
    public function it_fetches_projection_names(): void
    {
        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->fetchProjectionNames('foo', false, 10, 20)->shouldBeCalled();

        $wrapper = new ActionEventEmitterEventStore($eventStore->reveal(), new ProophActionEventEmitter());

        $wrapper->fetchProjectionNames('foo', false, 10, 20);
    }
}
