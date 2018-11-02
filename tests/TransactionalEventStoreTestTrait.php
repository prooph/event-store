<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore;

use ArrayIterator;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Exception\TransactionAlreadyStarted;
use Prooph\EventStore\Exception\TransactionNotStarted;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Prooph\EventStore\TransactionalEventStore;
use ProophTest\EventStore\Mock\UsernameChanged;

/**
 * Common tests for all transactional event store implementations
 */
trait TransactionalEventStoreTestTrait
{
    /**
     * @var TransactionalEventStore
     */
    protected $eventStore;

    /**
     * @test
     */
    public function it_works_transactional(): void
    {
        $streamName = $this->prophesize(StreamName::class);
        $streamName->toString()->willReturn('Prooph\Model\User')->shouldBeCalled();
        $streamName = $streamName->reveal();

        $stream = $this->prophesize(Stream::class);
        $stream->streamName()->willReturn($streamName);
        $stream->metadata()->willReturn(['foo' => 'bar'])->shouldBeCalled();
        $stream->streamEvents()->willReturn(new \ArrayIterator());

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream->reveal());

        $this->eventStore->commit();

        $this->assertTrue($this->eventStore->hasStream($streamName));
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
            $this->eventStore->appendTo(new StreamName('Prooph\Model\User'), new ArrayIterator([$secondStreamEvent]));
            $this->assertSame($this->eventStore, $eventStore);

            return 'Second Result';
        });

        $this->assertSame('Second Result', $transactionResult);

        $streamEvents = $this->eventStore->load(new StreamName('Prooph\Model\User'), 1);

        $this->assertCount(2, $streamEvents);
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

        $this->eventStore->rollback();

        $this->assertFalse($this->eventStore->hasStream($streamName));
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
    public function it_throws_exception_when_transaction_already_started(): void
    {
        $this->expectException(TransactionAlreadyStarted::class);

        $this->eventStore->beginTransaction();
        $this->eventStore->beginTransaction();
    }

    /**
     * @test
     */
    public function it_can_commit_empty_transaction(): void
    {
        $this->eventStore->beginTransaction();
        $this->eventStore->commit();

        $this->assertFalse($this->eventStore->inTransaction());
    }

    /**
     * @test
     */
    public function it_cannot_commit_twice(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->eventStore->beginTransaction();
        $this->eventStore->commit();
        $this->eventStore->commit();
    }

    /**
     * @test
     */
    public function it_can_rollback_empty_transaction(): void
    {
        $this->assertFalse($this->eventStore->inTransaction());
        $this->eventStore->beginTransaction();
        $this->assertTrue($this->eventStore->inTransaction());
        $this->eventStore->rollback();
        $this->assertFalse($this->eventStore->inTransaction());
    }

    /**
     * @test
     */
    public function it_cannot_rollback_twice(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->eventStore->beginTransaction();
        $this->eventStore->rollback();
        $this->eventStore->rollback();
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
    public function it_loads_and_saves_within_one_transaction(): void
    {
        $testStream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $streamNotFound = false;

        try {
            $this->eventStore->load($testStream->streamName());
        } catch (StreamNotFound $e) {
            $streamNotFound = true;
        }

        $this->assertTrue($streamNotFound);

        $this->eventStore->create($testStream);

        $this->eventStore->commit();
    }
}
