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

        $this->eventStore->getActionEventEmitter()->attachListener(
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
    public function it_throws_exception_when_listener_stops_loading_events_and_does_not_provide_loaded_events(): void
    {
        $this->expectException(StreamNotFound::class);

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $streamEventWithMetadata = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $streamEventWithMetadata = $streamEventWithMetadata->withAddedMetadata('snapshot', true);

        $this->eventStore->appendTo($stream->streamName(), new ArrayIterator([$streamEventWithMetadata]));

        $this->eventStore->getActionEventEmitter()->attachListener(
            'load',
            function (ActionEvent $event): void {
                $event->stopPropagation(true);
            },
            1000
        );

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('snapshot', Operator::EQUALS(), true);

        $this->eventStore->load($stream->streamName(), 1, null, $metadataMatcher);
    }

    /**
     * @test
     */
    public function it_does_not_append_events_when_listener_stops_propagation(): void
    {
        $recordedEvents = [];

        $this->eventStore->getActionEventEmitter()->attachListener(
            'create',
            function (ActionEvent $event) use (&$recordedEvents): void {
                foreach ($event->getParam('recordedEvents', new \ArrayIterator()) as $recordedEvent) {
                    $recordedEvents[] = $recordedEvent;
                }
            },
            -1000
        );

        $this->eventStore->getActionEventEmitter()->attachListener(
            'appendTo',
            function (ActionEvent $event) use (&$recordedEvents): void {
                foreach ($event->getParam('recordedEvents', new \ArrayIterator()) as $recordedEvent) {
                    $recordedEvents[] = $recordedEvent;
                }
            },
            -1000
        );

        $this->eventStore->create($this->getTestStream());

        $this->eventStore->getActionEventEmitter()->attachListener(
            'appendTo',
            function (ActionEvent $event): void {
                //$event->setParam('streamNotFound', true);
                $event->stopPropagation(true);
            },
            1000
        );

        $secondStreamEvent = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $this->eventStore->appendTo(new StreamName('user'), new ArrayIterator([$secondStreamEvent]));

        $this->assertCount(1, $this->eventStore->load(new StreamName('user'))->streamEvents());
    }

    /**
     * @test
     */
    public function it_uses_stream_provided_by_listener_when_listener_stops_propagation(): void
    {
        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $this->eventStore->getActionEventEmitter()->attachListener(
            'load',
            function (ActionEvent $event): void {
                $event->setParam('stream', new Stream(new StreamName('user'), new ArrayIterator()));
                $event->stopPropagation(true);
            },
            1000
        );

        $emptyStream = $this->eventStore->load($stream->streamName());

        $this->assertCount(0, $emptyStream->streamEvents());
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

        $this->eventStore->getActionEventEmitter()->attachListener(
            'load',
            function (ActionEvent $event): void {
                $streamEventWithMetadataButOtherUuid = UsernameChanged::with(
                    ['new_name' => 'John Doe'],
                    2
                );

                $streamEventWithMetadataButOtherUuid = $streamEventWithMetadataButOtherUuid->withAddedMetadata('snapshot', true);

                $streamName = $event->getParam('streamName');

                $event->setParam('stream', new Stream(
                        $streamName,
                        new ArrayIterator([$streamEventWithMetadataButOtherUuid]))
                );
                $event->stopPropagation(true);
            },
            1000
        );

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('snapshot', Operator::EQUALS(), true);

        $stream = $this->eventStore->load($stream->streamName(), 1, null, $metadataMatcher);
        $loadedEvents = $stream->streamEvents();

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

        $this->eventStore->getActionEventEmitter()->attachListener(
            'create',
            function (ActionEvent $event) use (&$recordedEvents): void {
                $stream = $event->getParam('stream');

                foreach ($stream->streamEvents() as $recordedEvent) {
                    $recordedEvents[] = $recordedEvent;
                }
            },
            -1000
        );

        $this->eventStore->getActionEventEmitter()->attachListener(
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

        $this->eventStore->getActionEventEmitter()->attachListener(
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

        $stream = $this->eventStore->load($streamName);

        $this->assertEquals('user', $stream->streamName()->toString());

        $this->assertCount(1, $stream->streamEvents());

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
}
