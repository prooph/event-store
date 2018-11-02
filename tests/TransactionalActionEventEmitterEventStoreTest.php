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

use PHPUnit\Framework\TestCase;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\Exception\TransactionAlreadyStarted;
use Prooph\EventStore\Exception\TransactionNotStarted;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Prooph\EventStore\TransactionalActionEventEmitterEventStore;

class TransactionalActionEventEmitterEventStoreTest extends TestCase
{
    use EventStoreTestStreamTrait;

    /**
     * @var TransactionalActionEventEmitterEventStore
     */
    protected $eventStore;

    protected function setUp(): void
    {
        $eventEmitter = new ProophActionEventEmitter(TransactionalActionEventEmitterEventStore::ALL_EVENTS);

        $this->eventStore = new TransactionalActionEventEmitterEventStore(new InMemoryEventStore(), $eventEmitter);
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
    public function it_wraps_up_code_in_transaction_properly(): void
    {
        $transactionResult = $this->eventStore->transactional(function () {
            $this->eventStore->create($this->getTestStream());

            return 'Result';
        });

        $this->assertSame('Result', $transactionResult);
    }
}
