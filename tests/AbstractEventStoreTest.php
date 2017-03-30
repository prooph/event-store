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
use PHPUnit\Framework\TestCase;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Exception\StreamExistsAlready;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Metadata\Operator;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\EventStore\Mock\TestDomainEvent;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;

/**
 * Common tests for all event store implementations
 */
abstract class AbstractEventStoreTest extends TestCase
{
    use EventStoreTestStreamTrait;

    /**
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @test
     */
    public function it_creates_a_new_stream_and_records_the_stream_events_and_deletes(): void
    {
        $streamName = new StreamName('Prooph\Model\User');

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $streamEvents = $this->eventStore->load($streamName);

        $this->assertCount(1, $streamEvents);

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
    public function it_appends_events_to_stream_and_records_them(): void
    {
        $this->eventStore->create($this->getTestStream());

        $secondStreamEvent = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $this->eventStore->appendTo(new StreamName('Prooph\Model\User'), new ArrayIterator([$secondStreamEvent]));

        $this->assertCount(2, $this->eventStore->load(new StreamName('Prooph\Model\User')));
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

        $loadedEvents = $this->eventStore->load($stream->streamName(), 2);

        $this->assertCount(2, $loadedEvents);

        $loadedEvents->rewind();

        $this->assertTrue($loadedEvents->current()->metadata()['snapshot']);
        $loadedEvents->next();
        $this->assertFalse($loadedEvents->current()->metadata()['snapshot']);

        $streamEvents = $this->eventStore->load($stream->streamName(), 2);

        $this->assertCount(2, $streamEvents);

        $streamEvents->rewind();

        $this->assertTrue($streamEvents->current()->metadata()['snapshot']);
        $streamEvents->next();
        $this->assertFalse($streamEvents->current()->metadata()['snapshot']);
    }

    /**
     * @test
     */
    public function it_loads_events_reverse_from_number(): void
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

        $loadedEvents = $this->eventStore->loadReverse($stream->streamName(), null, 2);

        $this->assertCount(2, $loadedEvents);

        $loadedEvents->rewind();

        $this->assertFalse($loadedEvents->current()->metadata()['snapshot']);
        $loadedEvents->next();
        $this->assertTrue($loadedEvents->current()->metadata()['snapshot']);

        $streamEvents = $this->eventStore->loadReverse($stream->streamName(), null, 2);

        $this->assertCount(2, $streamEvents);

        $streamEvents->rewind();

        $this->assertFalse($streamEvents->current()->metadata()['snapshot']);
        $streamEvents->next();
        $this->assertTrue($streamEvents->current()->metadata()['snapshot']);
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
            $streamEventVersion4,
        ]));

        $loadedEvents = $this->eventStore->load($stream->streamName(), 2, 2);

        $this->assertCount(2, $loadedEvents);

        $loadedEvents->rewind();

        $this->assertTrue($loadedEvents->current()->metadata()['snapshot']);
        $loadedEvents->next();
        $this->assertFalse($loadedEvents->current()->metadata()['snapshot']);

        $loadedEvents = $this->eventStore->load($stream->streamName(), 2, 2);

        $this->assertCount(2, $loadedEvents);

        $loadedEvents->rewind();

        $this->assertTrue($loadedEvents->current()->metadata()['snapshot']);
        $loadedEvents->next();
        $this->assertFalse($loadedEvents->current()->metadata()['snapshot']);
    }

    /**
     * @test
     */
    public function it_loads_events_reverse_from_number_with_count(): void
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

        $loadedEvents = $this->eventStore->loadReverse($stream->streamName(), 3, 2);

        $this->assertCount(2, $loadedEvents);

        $loadedEvents->rewind();

        $this->assertFalse($loadedEvents->current()->metadata()['snapshot']);
        $loadedEvents->next();
        $this->assertTrue($loadedEvents->current()->metadata()['snapshot']);
    }

    /**
     * @test
     * @dataProvider getMatchingMetadata
     */
    public function it_loads_events_by_matching_metadata(array $metadata): void
    {
        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $streamEventWithMetadata = TestDomainEvent::with(
            ['name' => 'Alex', 'email' => 'contact@prooph.de'],
            2
        );

        foreach ($metadata as $field => $value) {
            $streamEventWithMetadata = $streamEventWithMetadata->withAddedMetadata($field, $value);
        }

        $this->eventStore->appendTo($stream->streamName(), new ArrayIterator([$streamEventWithMetadata]));

        $metadataMatcher = new MetadataMatcher();

        foreach ($metadata as $field => $value) {
            $metadataMatcher = $metadataMatcher->withMetadataMatch($field, Operator::EQUALS(), $value);
        }

        $streamEvents = $this->eventStore->load($stream->streamName(), 1, null, $metadataMatcher);

        $this->assertCount(1, $streamEvents);

        $streamEvents->rewind();

        $currentMetadata = $streamEvents->current()->metadata();

        foreach ($metadata as $field => $value) {
            $this->assertEquals($value, $currentMetadata[$field]);
        }
    }

    /**
     * @test
     * @dataProvider getMatchingMetadata
     */
    public function it_loads_events_reverse_by_matching_metadata(array $metadata): void
    {
        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $streamEventWithMetadata = TestDomainEvent::with(
            ['name' => 'Alex', 'email' => 'contact@prooph.de'],
            2
        );

        foreach ($metadata as $field => $value) {
            $streamEventWithMetadata = $streamEventWithMetadata->withAddedMetadata($field, $value);
        }

        $this->eventStore->appendTo($stream->streamName(), new ArrayIterator([$streamEventWithMetadata]));

        $metadataMatcher = new MetadataMatcher();

        foreach ($metadata as $field => $value) {
            $metadataMatcher = $metadataMatcher->withMetadataMatch($field, Operator::EQUALS(), $value);
        }

        $streamEvents = $this->eventStore->loadReverse($stream->streamName(), 2, null, $metadataMatcher);

        $this->assertCount(1, $streamEvents);

        $streamEvents->rewind();

        $currentMetadata = $streamEvents->current()->metadata();

        foreach ($metadata as $field => $value) {
            $this->assertEquals($value, $currentMetadata[$field]);
        }
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

        $stream = new Stream(new StreamName('Prooph\Model\User'), new ArrayIterator([$event]));

        $this->eventStore->create($stream);

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('foo', Operator::EQUALS(), 'bar');
        $metadataMatcher = $metadataMatcher->withMetadataMatch('foo', Operator::NOT_EQUALS(), 'baz');
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int', Operator::GREATER_THAN(), 4);
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int2', Operator::GREATER_THAN_EQUALS(), 4);
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int3', Operator::LOWER_THAN(), 7);
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int4', Operator::LOWER_THAN_EQUALS(), 7);

        $streamEvents = $this->eventStore->load($stream->streamName(), 1, null, $metadataMatcher);

        $this->assertCount(1, $streamEvents);
    }

    /**
     * @test
     */
    public function it_returns_only_matched_metadata_2(): void
    {
        $event = UserCreated::with(['name' => 'John'], 1);
        $event = $event->withAddedMetadata('foo', 'bar');
        $event = $event->withAddedMetadata('int', 5);
        $event = $event->withAddedMetadata('int2', 4);
        $event = $event->withAddedMetadata('int3', 6);
        $event = $event->withAddedMetadata('int4', 7);

        $streamName = $this->prophesize(StreamName::class);
        $streamName->toString()->willReturn('Prooph\Model\User')->shouldBeCalled();
        $streamName = $streamName->reveal();

        $stream = $this->prophesize(Stream::class);
        $stream->streamName()->willReturn($streamName)->shouldBeCalled();
        $stream->metadata()->willReturn([])->shouldBeCalled();
        $stream->streamEvents()->willReturn(new \ArrayIterator([$event]))->shouldBeCalled();

        $this->eventStore->create($stream->reveal());

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('foo', Operator::EQUALS(), 'baz');

        $result = $this->eventStore->load($streamName, 1, null, $metadataMatcher);

        $this->assertFalse($result->valid());

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('foo', Operator::NOT_EQUALS(), 'bar');

        $result = $this->eventStore->load($streamName, 1, null, $metadataMatcher);

        $this->assertFalse($result->valid());

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int', Operator::GREATER_THAN(), 9);

        $result = $this->eventStore->load($streamName, 1, null, $metadataMatcher);

        $this->assertFalse($result->valid());

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int2', Operator::GREATER_THAN_EQUALS(), 10);

        $result = $this->eventStore->load($streamName, 1, null, $metadataMatcher);

        $this->assertFalse($result->valid());

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int3', Operator::LOWER_THAN(), 1);

        $result = $this->eventStore->load($streamName, 1, null, $metadataMatcher);

        $this->assertFalse($result->valid());

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int4', Operator::LOWER_THAN_EQUALS(), 1);

        $result = $this->eventStore->load($streamName, 1, null, $metadataMatcher);

        $this->assertFalse($result->valid());

        $this->expectException(InvalidArgumentException::class);

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher->withMetadataMatch('meta', Operator::EQUALS(), ['key' => 'value']);
    }

    /**
     * @test
     */
    public function it_returns_only_matched_metadata_reverse(): void
    {
        $event = UserCreated::with(['name' => 'John'], 1);
        $event = $event->withAddedMetadata('foo', 'bar');
        $event = $event->withAddedMetadata('int', 5);
        $event = $event->withAddedMetadata('int2', 4);
        $event = $event->withAddedMetadata('int3', 6);
        $event = $event->withAddedMetadata('int4', 7);

        $stream = new Stream(new StreamName('Prooph\Model\User'), new ArrayIterator([$event]));

        $this->eventStore->create($stream);

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('foo', Operator::EQUALS(), 'bar');
        $metadataMatcher = $metadataMatcher->withMetadataMatch('foo', Operator::NOT_EQUALS(), 'baz');
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int', Operator::GREATER_THAN(), 4);
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int2', Operator::GREATER_THAN_EQUALS(), 4);
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int3', Operator::LOWER_THAN(), 7);
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int4', Operator::LOWER_THAN_EQUALS(), 7);

        $streamEvents = $this->eventStore->loadReverse($stream->streamName(), 1, null, $metadataMatcher);

        $this->assertCount(1, $streamEvents);
    }

    /**
     * @test
     */
    public function it_returns_only_matched_metadata_2_reverse(): void
    {
        $event = UserCreated::with(['name' => 'John'], 1);
        $event = $event->withAddedMetadata('foo', 'bar');
        $event = $event->withAddedMetadata('int', 5);
        $event = $event->withAddedMetadata('int2', 4);
        $event = $event->withAddedMetadata('int3', 6);
        $event = $event->withAddedMetadata('int4', 7);

        $streamName = $this->prophesize(StreamName::class);
        $streamName->toString()->willReturn('Prooph\Model\User')->shouldBeCalled();
        $streamName = $streamName->reveal();

        $stream = $this->prophesize(Stream::class);
        $stream->streamName()->willReturn($streamName)->shouldBeCalled();
        $stream->metadata()->willReturn([])->shouldBeCalled();
        $stream->streamEvents()->willReturn(new \ArrayIterator([$event]))->shouldBeCalled();

        $this->eventStore->create($stream->reveal());

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('foo', Operator::EQUALS(), 'baz');

        $result = $this->eventStore->loadReverse($streamName, null, null, $metadataMatcher);

        $this->assertFalse($result->valid());

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('foo', Operator::NOT_EQUALS(), 'bar');

        $result = $this->eventStore->loadReverse($streamName, null, null, $metadataMatcher);

        $this->assertFalse($result->valid());

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int', Operator::GREATER_THAN(), 9);

        $result = $this->eventStore->loadReverse($streamName, null, null, $metadataMatcher);

        $this->assertFalse($result->valid());

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int2', Operator::GREATER_THAN_EQUALS(), 10);

        $result = $this->eventStore->loadReverse($streamName, null, null, $metadataMatcher);

        $this->assertFalse($result->valid());

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int3', Operator::LOWER_THAN(), 1);

        $this->eventStore->loadReverse($streamName, null, null, $metadataMatcher);

        $this->assertFalse($result->valid());

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int4', Operator::LOWER_THAN_EQUALS(), 1);

        $result = $this->eventStore->loadReverse($streamName, null, null, $metadataMatcher);

        $this->assertFalse($result->valid());

        $this->expectException(InvalidArgumentException::class);

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher->withMetadataMatch('meta', Operator::EQUALS(), ['key' => 'value']);
    }

    /**
     * @test
     */
    public function it_loads_empty_stream(): void
    {
        $streamName = new StreamName('Prooph\Model\User');

        $this->eventStore->create(new Stream($streamName, new ArrayIterator()));

        $it = $this->eventStore->load($streamName);

        $this->assertFalse($it->valid());
    }

    /**
     * @test
     */
    public function it_loads_reverse_empty_stream(): void
    {
        $streamName = new StreamName('Prooph\Model\User');

        $this->eventStore->create(new Stream($streamName, new ArrayIterator()));

        $it = $this->eventStore->loadReverse($streamName);

        $this->assertFalse($it->valid());
    }

    /**
     * @test
     */
    public function it_throws_stream_not_found_exception_if_it_loads_nothing(): void
    {
        $this->expectException(StreamNotFound::class);

        $stream = $this->getTestStream();

        $this->eventStore->load($stream->streamName());
    }

    /**
     * @test
     */
    public function it_throws_stream_not_found_exception_if_it_loads_nothing_reverse(): void
    {
        $this->expectException(StreamNotFound::class);

        $stream = $this->getTestStream();

        $this->eventStore->loadReverse($stream->streamName());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_asked_for_unknown_stream_metadata(): void
    {
        $this->expectException(StreamNotFound::class);

        $this->eventStore->fetchStreamMetadata(new StreamName('unknown'));
    }

    /**
     * @test
     */
    public function it_returns_metadata_when_asked_for_stream_metadata(): void
    {
        $stream = new Stream(new StreamName('Prooph\Model\User'), new ArrayIterator(), ['foo' => 'bar']);

        $this->eventStore->create($stream);

        $this->assertEquals(['foo' => 'bar'], $this->eventStore->fetchStreamMetadata($stream->streamName()));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_trying_to_delete_unknown_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $this->eventStore->delete(new StreamName('unknown'));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_trying_to_append_on_non_existing_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $event = UserCreated::with(['name' => 'Alex'], 1);

        $this->eventStore->appendTo(new StreamName('unknown'), new ArrayIterator([$event]));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_trying_to_load_non_existing_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $streamName = $this->prophesize(StreamName::class);
        $streamName->toString()->willReturn('test')->shouldBeCalled();

        $this->eventStore->load($streamName->reveal());
    }

    /**
     * @test
     */
    public function it_deletes_stream(): void
    {
        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $this->eventStore->delete($stream->streamName());

        $this->assertFalse($this->eventStore->hasStream($stream->streamName()));
    }

    /**
     * @test
     */
    public function it_can_check_for_stream_existence(): void
    {
        $streamName = new StreamName('Prooph\Model\User');

        $this->assertFalse($this->eventStore->hasStream($streamName));

        $this->eventStore->create($this->getTestStream());

        $this->assertTrue($this->eventStore->hasStream($streamName));
    }

    /**
     * @test
     */
    public function it_fetches_stream_names(): void
    {
        $streamNames = [];

        try {
            for ($i = 0; $i < 50; $i++) {
                $streamNames[] = 'user-' . $i;
                $streamNames[] = 'admin-' . $i;
                $this->eventStore->create(new Stream(new StreamName('user-' . $i), new \EmptyIterator(), ['foo' => 'bar']));
                $this->eventStore->create(new Stream(new StreamName('admin-' . $i), new \EmptyIterator(), ['foo' => 'bar']));
            }

            for ($i = 0; $i < 20; $i++) {
                $streamName = uniqid('rand');
                $streamNames[] = $streamName;
                $this->eventStore->create(new Stream(new StreamName($streamName), new \EmptyIterator()));
            }

            $this->assertCount(1, $this->eventStore->fetchStreamNames('user-0', null, 200, 0));
            $this->assertCount(120, $this->eventStore->fetchStreamNames(null, null, 200, 0));
            $this->assertCount(0, $this->eventStore->fetchStreamNames(null, null, 200, 200));
            $this->assertCount(10, $this->eventStore->fetchStreamNames(null, null, 10, 0));
            $this->assertCount(10, $this->eventStore->fetchStreamNames(null, null, 10, 10));
            $this->assertCount(5, $this->eventStore->fetchStreamNames(null, null, 10, 115));

            for ($i = 0; $i < 50; $i++) {
                $this->assertStringStartsWith('admin-', $this->eventStore->fetchStreamNames(null, null, 1, $i)[0]->toString());
            }

            for ($i = 50; $i < 70; $i++) {
                $this->assertStringStartsWith('rand', $this->eventStore->fetchStreamNames(null, null, 1, $i)[0]->toString());
            }

            for ($i = 0; $i < 50; $i++) {
                $this->assertStringStartsWith('user-', $this->eventStore->fetchStreamNames(null, null, 1, $i + 70)[0]->toString());
            }

            $this->assertCount(30, $this->eventStore->fetchStreamNamesRegex('s.*er-', null, 30, 0));
            $this->assertCount(20, $this->eventStore->fetchStreamNamesRegex('s.*er-', null, 20, 10));
            $this->assertCount(30, $this->eventStore->fetchStreamNamesRegex('n.*-', (new MetadataMatcher())->withMetadataMatch('foo', Operator::EQUALS(), 'bar'), 30, 0));
            $this->assertCount(0, $this->eventStore->fetchStreamNamesRegex('n.*-', (new MetadataMatcher())->withMetadataMatch('foo', Operator::NOT_EQUALS(), 'bar'), 30, 0));
            $this->assertCount(0, $this->eventStore->fetchStreamNames(null, (new MetadataMatcher())->withMetadataMatch('foo', Operator::NOT_EQUALS(), 'bar'), 30, 0));
        } finally {
            foreach ($streamNames as $streamName) {
                $this->eventStore->delete(new StreamName($streamName));
            }
        }
    }

    /**
     * @test
     */
    public function it_throws_exception_when_fetching_stream_names_using_invalid_regex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regex pattern given');

        $this->eventStore->fetchStreamNamesRegex('/invalid)/', null, 10, 0);
    }

    /**
     * @test
     */
    public function it_fetches_stream_categories(): void
    {
        $streamNames = [];

        try {
            for ($i = 0; $i < 5; $i++) {
                $streamNames[] = 'foo-' . $i;
                $streamNames[] = 'bar-' . $i;
                $streamNames[] = 'baz-' . $i;
                $streamNames[] = 'bam-' . $i;
                $streamNames[] = 'foobar-' . $i;
                $streamNames[] = 'foobaz-' . $i;
                $streamNames[] = 'foobam-' . $i;
                $this->eventStore->create(new Stream(new StreamName('foo-' . $i), new \EmptyIterator()));
                $this->eventStore->create(new Stream(new StreamName('bar-' . $i), new \EmptyIterator()));
                $this->eventStore->create(new Stream(new StreamName('baz-' . $i), new \EmptyIterator()));
                $this->eventStore->create(new Stream(new StreamName('bam-' . $i), new \EmptyIterator()));
                $this->eventStore->create(new Stream(new StreamName('foobar-' . $i), new \EmptyIterator()));
                $this->eventStore->create(new Stream(new StreamName('foobaz-' . $i), new \EmptyIterator()));
                $this->eventStore->create(new Stream(new StreamName('foobam-' . $i), new \EmptyIterator()));
            }

            for ($i = 0; $i < 20; $i++) {
                $streamName = uniqid('rand');
                $streamNames[] = $streamName;
                $this->eventStore->create(new Stream(new StreamName($streamName), new \EmptyIterator()));
            }

            $this->assertCount(7, $this->eventStore->fetchCategoryNames(null, 20, 0));
            $this->assertCount(0, $this->eventStore->fetchCategoryNames(null, 20, 20));
            $this->assertCount(3, $this->eventStore->fetchCategoryNames(null, 3, 0));
            $this->assertCount(3, $this->eventStore->fetchCategoryNames(null, 3, 3));
            $this->assertCount(5, $this->eventStore->fetchCategoryNames(null, 10, 2));

            $this->assertCount(1, $this->eventStore->fetchCategoryNames('foo', 20, 0));
            $this->assertCount(4, $this->eventStore->fetchCategoryNamesRegex('^foo', 20, 0));
            $this->assertCount(2, $this->eventStore->fetchCategoryNamesRegex('^foo', 2, 2));
        } finally {
            foreach ($streamNames as $streamName) {
                $this->eventStore->delete(new StreamName($streamName));
            }
        }
    }

    /**
     * @test
     */
    public function it_throws_exception_when_fetching_stream_categories_using_invalid_regex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regex pattern given');

        $this->eventStore->fetchCategoryNamesRegex('invalid)', 10, 0);
    }

    /**
     * @test
     */
    public function it_throws_exception_given_invalid_metadata_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher->withMetadataMatch('key', Operator::EQUALS(), ['foo' => 'bar']);
    }

    public function getMatchingMetadata(): array
    {
        return [
            [['snapshot' => true]],
            [['some_id' => 123]],
            [['fuu' => 'bar']],
            [['snapshot' => true, 'some_id' => 123, 'fuu' => 'bar']],
        ];
    }
}
