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
use Prooph\EventStore\Exception\StreamExistsAlready;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Metadata\Operator;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\EventStore\Mock\TestDomainEvent;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;

class InMemoryEventStoreTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_new_stream_and_records_the_stream_events(): void
    {
        $recordedEvents = [];

        $this->eventStore->getActionEventEmitter()->attachListener(
            'create',
            function (ActionEvent $event) use (&$recordedEvents): void {
                foreach ($event->getParam('streamEvents', new \ArrayIterator()) as $recordedEvent) {
                    $recordedEvents[] = $recordedEvent;
                }
            },
            -1000
        );

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $stream = $this->eventStore->load(new StreamName('user'));

        $this->assertEquals('user', $stream->streamName()->toString());

        $this->assertCount(1, $stream->streamEvents());

        $this->assertCount(1, $recordedEvents);

        $this->assertEquals(
            [
                'foo' => 'bar',
            ],
            $this->eventStore->fetchStreamMetadata(new StreamName('user'))
        );

        $this->assertTrue($this->eventStore->hasStream(new StreamName('user')));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_listener_stops_propagation(): void
    {
        $this->expectException(StreamExistsAlready::class);

        $this->eventStore->getActionEventEmitter()->attachListener(
            'create',
            function (ActionEvent $event): void {
                $event->stopPropagation(true);
            },
            1000
        );

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);
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
                foreach ($event->getParam('streamEvents', new \ArrayIterator()) as $recordedEvent) {
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

        $this->assertEquals(2, count($recordedEvents));
    }

    /**
     * @test
     */
    public function it_does_not_append_events_when_listener_stops_propagation(): void
    {
        $this->expectException(StreamNotFound::class);

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
                $event->setParam('stream', false);
                $event->stopPropagation(true);
            },
            1000
        );

        $secondStreamEvent = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $this->eventStore->appendTo(new StreamName('user'), new ArrayIterator([$secondStreamEvent]));
    }

    /**
     * @test
     */
    public function it_loads_events_by_matching_metadata(): void
    {
        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $streamEventWithMetadata = TestDomainEvent::with(
            ['name' => 'Alex', 'email' => 'contact@prooph.de'],
            1
        );

        $streamEventWithMetadata = $streamEventWithMetadata->withAddedMetadata('snapshot', true);

        $this->eventStore->appendTo($stream->streamName(), new ArrayIterator([$streamEventWithMetadata]));

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('snapshot', Operator::EQUALS(), true);

        $stream = $this->eventStore->load($stream->streamName(), 1, null, $metadataMatcher);

        $this->assertCount(1, $stream->streamEvents());

        $stream->streamEvents()->rewind();

        $this->assertTrue($stream->streamEvents()->current()->metadata()['snapshot']);
    }

    /**
     * @test
     */
    public function it_loads_events_from_number(): void
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

        $this->eventStore->appendTo($stream->streamName(), new ArrayIterator([$streamEventVersion2, $streamEventVersion3]));

        $stream = $this->eventStore->load($stream->streamName(), 2);
        $loadedEvents = $stream->streamEvents();

        $this->assertCount(2, $loadedEvents);

        $loadedEvents->rewind();

        $this->assertTrue($loadedEvents->current()->metadata()['snapshot']);
        $loadedEvents->next();
        $this->assertFalse($loadedEvents->current()->metadata()['snapshot']);

        $stream = $this->eventStore->load($stream->streamName(), 2);

        $this->assertCount(2, $stream->streamEvents());

        $stream->streamEvents()->rewind();

        $this->assertTrue($stream->streamEvents()->current()->metadata()['snapshot']);
        $stream->streamEvents()->next();
        $this->assertFalse($stream->streamEvents()->current()->metadata()['snapshot']);
    }

    /**
     * @test
     */
    public function it_loads_events_from_number_with_count(): void
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
            $streamEventVersion4
        ]));

        $stream = $this->eventStore->load($stream->streamName(), 2, 2);
        $loadedEvents = $stream->streamEvents();

        $this->assertCount(2, $loadedEvents);

        $loadedEvents->rewind();

        $this->assertTrue($loadedEvents->current()->metadata()['snapshot']);
        $loadedEvents->next();
        $this->assertFalse($loadedEvents->current()->metadata()['snapshot']);

        $stream = $this->eventStore->load($stream->streamName(), 2, 2);

        $loadedEvents = $stream->streamEvents();

        $this->assertCount(2, $loadedEvents);

        $loadedEvents->rewind();

        $this->assertTrue($loadedEvents->current()->metadata()['snapshot']);
        $loadedEvents->next();
        $this->assertFalse($loadedEvents->current()->metadata()['snapshot']);
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
            $streamEventVersion4
        ]));

        $stream = $this->eventStore->loadReverse($stream->streamName(), 3, 2);
        $loadedEvents = $stream->streamEvents();

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

        $stream = $this->eventStore->load($stream->streamName(), 1, null, $metadataMatcher);
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
    public function it_breaks_loading_a_stream_when_listener_stops_propagation_and_provides_stream_with_wrong_name(): void
    {
        $this->expectException(StreamNotFound::class);

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $this->eventStore->getActionEventEmitter()->attachListener(
            'load',
            function (ActionEvent $event): void {
                $event->setParam('stream', new Stream(new StreamName('EmptyStream'), new ArrayIterator()));
                $event->stopPropagation(true);
            },
            1000
        );

        $this->eventStore->load(new StreamName('user'));
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
    public function it_throws_stream_not_found_exception_if_adapter_loads_nothing(): void
    {
        $this->expectException(StreamNotFound::class);

        $stream = $this->getTestStream();

        $this->eventStore->load($stream->streamName());
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
    public function it_returns_metadata_when_asked_for_stream_metadata(): void
    {
        $streamName = $this->prophesize(StreamName::class);
        $streamName->toString()->willReturn('test')->shouldBeCalled();
        $streamName = $streamName->reveal();

        $stream = $this->prophesize(Stream::class);
        $stream->streamName()->willReturn($streamName);
        $stream->metadata()->willReturn(['foo' => 'bar'])->shouldBeCalled();
        $stream->streamEvents()->willReturn(new \ArrayIterator());

        $this->eventStore->create($stream->reveal());

        $this->assertEquals(
            [
                'foo' => 'bar'
            ],
            $this->eventStore->fetchStreamMetadata($streamName)
        );
    }

    /**
     * @test
     */
    public function it_returns_only_matched_metadata(): void
    {
        $event = UserCreated::with(['name' => 'John'], 1);
        $event = $event->withAddedMetadata('foo', 'bar');
        $event = $event->withAddedMetadata('int', 5);
        $event = $event->withAddedMetadata('int2', 4);
        $event = $event->withAddedMetadata('int3', 6);
        $event = $event->withAddedMetadata('int4', 7);

        $streamName = $this->prophesize(StreamName::class);
        $streamName->toString()->willReturn('test')->shouldBeCalled();
        $streamName = $streamName->reveal();

        $stream = $this->prophesize(Stream::class);
        $stream->streamName()->willReturn($streamName);
        $stream->metadata()->willReturn([])->shouldBeCalled();
        $stream->streamEvents()->willReturn(new \ArrayIterator([$event]));

        $this->eventStore->create($stream->reveal());

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('foo', Operator::EQUALS(), 'bar');
        $metadataMatcher = $metadataMatcher->withMetadataMatch('foo', Operator::NOT_EQUALS(), 'baz');
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int', Operator::GREATER_THAN(), 4);
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int2', Operator::GREATER_THAN_EQUALS(), 4);
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int3', Operator::LOWER_THAN(), 7);
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int4', Operator::LOWER_THAN_EQUALS(), 7);

        $stream = $this->eventStore->load($streamName, 1, null, $metadataMatcher);

        $this->assertCount(1, $stream->streamEvents());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_trying_to_append_on_non_existing_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $streamName = $this->prophesize(StreamName::class);
        $streamName->toString()->willReturn('test');

        $this->eventStore->appendTo($streamName->reveal(), new \ArrayIterator());
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

    private function getTestStream(): Stream
    {
        $streamEvent = UserCreated::with(
            ['name' => 'Alex', 'email' => 'contact@prooph.de'],
            1
        );

        return new Stream(new StreamName('user'), new ArrayIterator([$streamEvent]), ['foo' => 'bar']);
    }
}
