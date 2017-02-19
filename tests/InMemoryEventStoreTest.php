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
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Exception\StreamExistsAlready;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Exception\TransactionAlreadyStarted;
use Prooph\EventStore\Exception\TransactionNotStarted;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Metadata\Operator;
use Prooph\EventStore\Projection\ProjectionStatus;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\EventStore\Mock\TestDomainEvent;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;

class InMemoryEventStoreTest extends EventStoreTestCase
{
    /**
     * @test
     */
    public function it_creates_a_new_stream_and_records_the_stream_events_and_deletes(): void
    {
        $streamName = new StreamName('user');

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
    public function it_throws_stream_not_found_exception_when_trying_to_update_metadata_on_unknown_stream(): void
    {
        $this->expectException(StreamNotFound::class);

        $this->eventStore->updateStreamMetadata(new StreamName('unknown'), []);
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
    public function it_appends_events_to_stream_and_records_them(): void
    {
        $this->eventStore->create($this->getTestStream());

        $secondStreamEvent = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $this->eventStore->appendTo(new StreamName('user'), new ArrayIterator([$secondStreamEvent]));

        $this->assertCount(2, $this->eventStore->load(new StreamName('user')));
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

        $streamEvents = $this->eventStore->load($stream->streamName(), 1, null, $metadataMatcher);

        $this->assertCount(1, $streamEvents);

        $streamEvents->rewind();

        $this->assertTrue($streamEvents->current()->metadata()['snapshot']);
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

        $streamName = $this->prophesize(StreamName::class);
        $streamName->toString()->willReturn('unknown')->shouldBeCalled();

        $this->eventStore->fetchStreamMetadata($streamName->reveal());
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
                'foo' => 'bar',
            ],
            $this->eventStore->fetchStreamMetadata($streamName)
        );
    }

    /**
     * @test
     */
    public function it_works_transactional(): void
    {
        $streamName = $this->prophesize(StreamName::class);
        $streamName->toString()->willReturn('test')->shouldBeCalled();
        $streamName = $streamName->reveal();

        $stream = $this->prophesize(Stream::class);
        $stream->streamName()->willReturn($streamName);
        $stream->metadata()->willReturn(['foo' => 'bar'])->shouldBeCalled();
        $stream->streamEvents()->willReturn(new \ArrayIterator());

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream->reveal());

        $this->assertFalse($this->eventStore->hasStream($streamName));

        $this->eventStore->commit();

        $this->assertTrue($this->eventStore->hasStream($streamName));
    }

    /**
     * @test
     */
    public function it_rolls_back_transaction(): void
    {
        $streamName = $this->prophesize(StreamName::class);
        $streamName->toString()->willReturn('test')->shouldBeCalled();
        $streamName = $streamName->reveal();

        $stream = $this->prophesize(Stream::class);
        $stream->streamName()->willReturn($streamName);
        $stream->metadata()->willReturn(['foo' => 'bar'])->shouldBeCalled();
        $stream->streamEvents()->willReturn(new \ArrayIterator());

        $this->eventStore->beginTransaction();

        $this->assertTrue($this->eventStore->inTransaction());

        $this->eventStore->create($stream->reveal());

        $this->assertFalse($this->eventStore->hasStream($streamName));

        $this->eventStore->rollback();

        $this->assertFalse($this->eventStore->hasStream($streamName));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_transaction_started_on_commit(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->eventStore->commit();
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_transaction_started_on_rollback(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->eventStore->rollback();
    }

    /**
     * @test
     */
    public function it_throws_exception_when_transaction_already_started(): void
    {
        $this->expectException(TransactionAlreadyStarted::class);

        $this->eventStore->beginTransaction();
        $this->eventStore->beginTransaction();
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

        $streamEvents = $this->eventStore->load($streamName, 1, null, $metadataMatcher);

        $this->assertCount(1, $streamEvents);
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

        $streamEvents = $this->eventStore->loadReverse($streamName, PHP_INT_MAX, null, $metadataMatcher);

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
        $streamName->toString()->willReturn('test')->shouldBeCalled();
        $streamName = $streamName->reveal();

        $stream = $this->prophesize(Stream::class);
        $stream->streamName()->willReturn($streamName)->shouldBeCalled();
        $stream->metadata()->willReturn([])->shouldBeCalled();
        $stream->streamEvents()->willReturn(new \ArrayIterator([$event]))->shouldBeCalled();

        $this->eventStore->create($stream->reveal());

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('foo', Operator::EQUALS(), 'baz');

        $result = $this->eventStore->load($streamName, 1, null, $metadataMatcher);

        $this->assertEmpty($result);

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('foo', Operator::NOT_EQUALS(), 'bar');

        $result = $this->eventStore->load($streamName, 1, null, $metadataMatcher);

        $this->assertEmpty($result);

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int', Operator::GREATER_THAN(), 9);

        $result = $this->eventStore->load($streamName, 1, null, $metadataMatcher);

        $this->assertEmpty($result);

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int2', Operator::GREATER_THAN_EQUALS(), 10);

        $result = $this->eventStore->load($streamName, 1, null, $metadataMatcher);

        $this->assertEmpty($result);

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int3', Operator::LOWER_THAN(), 1);

        $result = $this->eventStore->load($streamName, 1, null, $metadataMatcher);

        $this->assertEmpty($result);

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int4', Operator::LOWER_THAN_EQUALS(), 1);

        $result = $this->eventStore->load($streamName, 1, null, $metadataMatcher);

        $this->assertEmpty($result);

        $this->expectException(InvalidArgumentException::class);

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher->withMetadataMatch('meta', Operator::EQUALS(), ['key' => 'value']);
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
        $streamName->toString()->willReturn('test')->shouldBeCalled();
        $streamName = $streamName->reveal();

        $stream = $this->prophesize(Stream::class);
        $stream->streamName()->willReturn($streamName)->shouldBeCalled();
        $stream->metadata()->willReturn([])->shouldBeCalled();
        $stream->streamEvents()->willReturn(new \ArrayIterator([$event]))->shouldBeCalled();

        $this->eventStore->create($stream->reveal());

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('foo', Operator::EQUALS(), 'baz');

        $result = $this->eventStore->loadReverse($streamName, PHP_INT_MAX, null, $metadataMatcher);

        $this->assertEmpty($result);

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('foo', Operator::NOT_EQUALS(), 'bar');

        $result = $this->eventStore->loadReverse($streamName, PHP_INT_MAX, null, $metadataMatcher);

        $this->assertEmpty($result);

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int', Operator::GREATER_THAN(), 9);

        $result = $this->eventStore->loadReverse($streamName, PHP_INT_MAX, null, $metadataMatcher);

        $this->assertEmpty($result);

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int2', Operator::GREATER_THAN_EQUALS(), 10);

        $result = $this->eventStore->loadReverse($streamName, PHP_INT_MAX, null, $metadataMatcher);

        $this->assertEmpty($result);

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int3', Operator::LOWER_THAN(), 1);

        $this->eventStore->loadReverse($streamName, PHP_INT_MAX, null, $metadataMatcher);

        $this->assertEmpty($result);

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher = $metadataMatcher->withMetadataMatch('int4', Operator::LOWER_THAN_EQUALS(), 1);

        $result = $this->eventStore->loadReverse($streamName, PHP_INT_MAX, null, $metadataMatcher);

        $this->assertEmpty($result);

        $this->expectException(InvalidArgumentException::class);

        $metadataMatcher = new MetadataMatcher();
        $metadataMatcher->withMetadataMatch('meta', Operator::EQUALS(), ['key' => 'value']);
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
    public function it_should_rollback_and_throw_exception_in_case_of_transaction_fail(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Transaction failed');

        $eventStore = $this->eventStore;

        $this->eventStore->transactional(function (EventStore $es) use ($eventStore) {
            $this->assertSame($es, $eventStore);
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
            $this->assertSame($this->eventStore, $eventStore);
        });
        $this->assertTrue($transactionResult);
    }

    /**
     * @test
     */
    public function it_wraps_up_code_in_transaction_properly(): void
    {
        $transactionResult = $this->eventStore->transactional(function (EventStore $eventStore) {
            $this->eventStore->create($this->getTestStream());
            $this->assertSame($this->eventStore, $eventStore);

            return 'Result';
        });

        $this->assertSame('Result', $transactionResult);

        $secondStreamEvent = UsernameChanged::with(
            ['new_name' => 'John Doe'],
            2
        );

        $transactionResult = $this->eventStore->transactional(function (EventStore $eventStore) use ($secondStreamEvent) {
            $this->eventStore->appendTo(new StreamName('user'), new ArrayIterator([$secondStreamEvent]));
            $this->assertSame($this->eventStore, $eventStore);

            return 'Second Result';
        });

        $this->assertSame('Second Result', $transactionResult);

        $streamEvents = $this->eventStore->load(new StreamName('user'), 1);

        $this->assertCount(2, $streamEvents);
    }

    /**
     * @test
     */
    public function it_cannot_delete_projections(): void
    {
        $this->expectException(RuntimeException::class);

        $this->eventStore->deleteProjection('foo', true);
    }

    /**
     * @test
     */
    public function it_cannot_reset_projections(): void
    {
        $this->expectException(RuntimeException::class);

        $this->eventStore->resetProjection('foo');
    }

    /**
     * @test
     */
    public function it_cannot_stop_projections(): void
    {
        $this->expectException(RuntimeException::class);

        $this->eventStore->stopProjection('foo');
    }

    /**
     * @test
     */
    public function it_fetches_stream_names(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $this->eventStore->create(new Stream(new StreamName('user-' . $i), new \EmptyIterator()));
            $this->eventStore->create(new Stream(new StreamName('admin-' . $i), new \EmptyIterator(), ['foo' => 'bar']));
        }

        for ($i = 0; $i < 20; $i++) {
            $this->eventStore->create(new Stream(new StreamName(uniqid('rand')), new \EmptyIterator()));
        }

        $this->assertCount(120, $this->eventStore->fetchStreamNames(null, false, null, 200, 0));
        $this->assertCount(0, $this->eventStore->fetchStreamNames(null, false, null, 200, 200));
        $this->assertCount(10, $this->eventStore->fetchStreamNames(null, false, null, 10, 0));
        $this->assertCount(10, $this->eventStore->fetchStreamNames(null, false, null, 10, 10));
        $this->assertCount(5, $this->eventStore->fetchStreamNames(null, false, null, 10, 115));

        for ($i = 0; $i < 50; $i++) {
            $this->assertStringStartsWith('admin-', $this->eventStore->fetchStreamNames(null, false, null, 1, $i)[0]->toString());
        }

        for ($i = 50; $i < 70; $i++) {
            $this->assertStringStartsWith('rand', $this->eventStore->fetchStreamNames(null, false, null, 1, $i)[0]->toString());
        }

        for ($i = 0; $i < 50; $i++) {
            $this->assertStringStartsWith('user-', $this->eventStore->fetchStreamNames(null, false, null, 1, $i + 70)[0]->toString());
        }

        $this->assertCount(30, $this->eventStore->fetchStreamNames('ser-', true, null, 30, 0));
        $this->assertCount(30, $this->eventStore->fetchStreamNames('n-', true, (new MetadataMatcher())->withMetadataMatch('foo', Operator::EQUALS(), 'bar'), 30, 0));
        $this->assertCount(0, $this->eventStore->fetchStreamNames('n-', true, (new MetadataMatcher())->withMetadataMatch('foo', Operator::NOT_EQUALS(), 'bar'), 30, 0));
        $this->assertCount(0, $this->eventStore->fetchStreamNames(null, false, (new MetadataMatcher())->withMetadataMatch('foo', Operator::NOT_EQUALS(), 'bar'), 30, 0));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_fetching_stream_names_using_regex_and_no_filter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No regex pattern given');

        $this->eventStore->fetchStreamNames(null, true, null, 10, 0);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_fetching_stream_names_using_invalid_regex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regex pattern given');

        $this->eventStore->fetchStreamNames('/invalid)/', true, null, 10, 0);
    }

    /**
     * @test
     */
    public function it_fetches_stream_categories(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->eventStore->create(new Stream(new StreamName('foo-' . $i), new \EmptyIterator()));
            $this->eventStore->create(new Stream(new StreamName('bar-' . $i), new \EmptyIterator()));
            $this->eventStore->create(new Stream(new StreamName('baz-' . $i), new \EmptyIterator()));
            $this->eventStore->create(new Stream(new StreamName('bam-' . $i), new \EmptyIterator()));
            $this->eventStore->create(new Stream(new StreamName('foobar-' . $i), new \EmptyIterator()));
            $this->eventStore->create(new Stream(new StreamName('foobaz-' . $i), new \EmptyIterator()));
            $this->eventStore->create(new Stream(new StreamName('foobam-' . $i), new \EmptyIterator()));
        }

        for ($i = 0; $i < 20; $i++) {
            $this->eventStore->create(new Stream(new StreamName(uniqid('rand')), new \EmptyIterator()));
        }

        $this->assertCount(7, $this->eventStore->fetchCategoryNames(null, false, 20, 0));
        $this->assertCount(0, $this->eventStore->fetchCategoryNames(null, false, 20, 20));
        $this->assertCount(3, $this->eventStore->fetchCategoryNames(null, false, 3, 0));
        $this->assertCount(3, $this->eventStore->fetchCategoryNames(null, false, 3, 3));
        $this->assertCount(5, $this->eventStore->fetchCategoryNames(null, false, 10, 2));

        $this->assertCount(1, $this->eventStore->fetchCategoryNames('foo', false, 20, 0));
        $this->assertCount(4, $this->eventStore->fetchCategoryNames('foo', true, 20, 0));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_fetching_stream_categories_using_regex_and_no_filter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No regex pattern given');

        $this->eventStore->fetchCategoryNames(null, true, 10, 0);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_fetching_stream_categories_using_invalid_regex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regex pattern given');

        $this->eventStore->fetchCategoryNames('invalid)', true, 10, 0);
    }

    /**
     * @test
     */
    public function it_fetches_projection_names(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $this->eventStore->createProjection('user-' . $i);
        }

        for ($i = 0; $i < 20; $i++) {
            $this->eventStore->createProjection(uniqid('rand'));
        }

        $this->assertCount(70, $this->eventStore->fetchProjectionNames(null, false, 200, 0));
        $this->assertCount(0, $this->eventStore->fetchProjectionNames(null, false, 200, 100));
        $this->assertCount(10, $this->eventStore->fetchProjectionNames(null, false, 10, 0));
        $this->assertCount(10, $this->eventStore->fetchProjectionNames(null, false, 10, 10));
        $this->assertCount(5, $this->eventStore->fetchProjectionNames(null, false, 10, 65));

        for ($i = 50; $i < 70; $i++) {
            $this->assertStringStartsWith('rand', $this->eventStore->fetchProjectionNames(null, false, 1, $i)[0]);
        }

        $this->assertCount(30, $this->eventStore->fetchProjectionNames('ser-', true, 30, 0));
        $this->assertCount(0, $this->eventStore->fetchProjectionNames('n-', true, 30, 0));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_fetching_projection_names_using_regex_and_no_filter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No regex pattern given');

        $this->eventStore->fetchProjectionNames(null, true, 10, 0);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_fetching_projection_names_using_invalid_regex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regex pattern given');

        $this->eventStore->fetchProjectionNames('invalid)', true, 10, 0);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_asked_for_unknown_projection_status(): void
    {
        $this->expectException(RuntimeException::class);

        $this->eventStore->fetchProjectionStatus('unkown');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_asked_for_unknown_projection_stream_positions(): void
    {
        $this->expectException(RuntimeException::class);

        $this->eventStore->fetchProjectionStreamPositions('unkown');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_asked_for_unknown_projection_state(): void
    {
        $this->expectException(RuntimeException::class);

        $this->eventStore->fetchProjectionState('unkown');
    }

    /**
     * @test
     */
    public function it_fetches_projection_status(): void
    {
        $projection = $this->eventStore->createProjection('test-projection');

        $this->assertSame(ProjectionStatus::IDLE(), $this->eventStore->fetchProjectionStatus('test-projection'));
    }

    /**
     * @test
     */
    public function it_fetches_projection_stream_positions(): void
    {
        $projection = $this->eventStore->createProjection('test-projection');

        $this->assertSame(null, $this->eventStore->fetchProjectionStreamPositions('test-projection'));
    }

    /**
     * @test
     */
    public function it_fetches_projection_state(): void
    {
        $projection = $this->eventStore->createProjection('test-projection');

        $this->assertSame([], $this->eventStore->fetchProjectionState('test-projection'));
    }
}
