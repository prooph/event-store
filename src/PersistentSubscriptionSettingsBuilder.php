<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2019 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use Prooph\EventStore\Common\SystemConsumerStrategies;
use Prooph\EventStore\Exception\InvalidArgumentException;

class PersistentSubscriptionSettingsBuilder
{
    /**
     * Tells the subscription to resolve link events.
     * @var bool
     */
    private $resolveLinkTos = false;
    /**
     * Start the subscription from the position-of the event in the stream.
     * If the value is set to `-1` that the subscription should start from
     * where the stream is when the subscription is first connected.
     * @var int
     */
    private $startFrom = -1;
    /**
     * Tells the backend to measure timings on the clients so statistics will contain histograms of them.
     * @var bool
     */
    private $extraStatistics = false;
    /**
     * The amount of time the system should try to checkpoint after.
     * @var int
     */
    private $checkPointAfterMilliseconds = 2000;
    /**
     * The size of the live buffer (in memory) before resorting to paging.
     * @var int
     */
    private $liveBufferSize = 500;
    /**
     * The size of the read batch when in paging mode.
     * @var int
     */
    private $readBatchSize = 20;
    /**
     * The number of messages that should be buffered when in paging mode.
     * @var int
     */
    private $bufferSize = 500;
    /**
     * The maximum number of messages not checkpointed before forcing a checkpoint.
     * @var int
     */
    private $maxCheckPointCount = 1000;
    /**
     * Sets the number of times a message should be retried before considered a bad message.
     * @var int
     */
    private $maxRetryCount = 10;
    /**
     * Sets the maximum number of allowed subscribers.
     * @var int
     */
    private $maxSubscriberCount = 0;
    /**
     * Sets the timeout for a client before the message will be retried.
     * @var int
     */
    private $messageTimeoutMilliseconds = 30000;
    /**
     * The minimum number of messages to write a checkpoint for.
     * @var int
     */
    private $minCheckPointCount = 10;
    /** @var string */
    private $namedConsumerStrategy = SystemConsumerStrategies::ROUND_ROBIN;

    /** @internal */
    public function __construct()
    {
    }

    public function withExtraStatistics(): self
    {
        $this->extraStatistics = true;

        return $this;
    }

    public function resolveLinkTos(): self
    {
        $this->resolveLinkTos = true;

        return $this;
    }

    public function doNotResolveLinkTos(): self
    {
        $this->resolveLinkTos = false;

        return $this;
    }

    public function preferRoundRobin(): self
    {
        $this->namedConsumerStrategy = SystemConsumerStrategies::ROUND_ROBIN;

        return $this;
    }

    public function preferDispatchToSingle(): self
    {
        $this->namedConsumerStrategy = SystemConsumerStrategies::DISPATCH_TO_SINGLE;

        return $this;
    }

    public function startFromBeginning(): self
    {
        $this->startFrom = 0;

        return $this;
    }

    public function startFrom(int $position): self
    {
        $this->startFrom = $position;

        return $this;
    }

    public function withMessageTimeoutOf(int $timeout): self
    {
        $this->messageTimeoutMilliseconds = $timeout;

        return $this;
    }

    public function dontTimeoutMessages(): self
    {
        $this->messageTimeoutMilliseconds = 0;

        return $this;
    }

    public function checkPointAfterMilliseconds(int $time): self
    {
        $this->checkPointAfterMilliseconds = $time;

        return $this;
    }

    public function minimumCheckPointCountOf(int $count): self
    {
        $this->minCheckPointCount = $count;

        return $this;
    }

    public function maximumCheckPointCountOf(int $count): self
    {
        $this->maxCheckPointCount = $count;

        return $this;
    }

    public function withMaxRetriesOf(int $count): self
    {
        if ($count < 0) {
            throw new InvalidArgumentException('MaxRetries cannot be negative');
        }

        $this->maxRetryCount = $count;

        return $this;
    }

    public function withLiveBufferSizeOf(int $count): self
    {
        if ($count < 0) {
            throw new InvalidArgumentException('LiveBufferSize cannot be negative');
        }

        $this->liveBufferSize = $count;

        return $this;
    }

    public function withReadBatchOf(int $count): self
    {
        if ($count < 0) {
            throw new InvalidArgumentException('ReadBatchSize cannot be negative');
        }

        $this->readBatchSize = $count;

        return $this;
    }

    public function withBufferSizeOf(int $count): self
    {
        if ($count < 0) {
            throw new InvalidArgumentException('BufferSize cannot be negative');
        }

        $this->bufferSize = $count;

        return $this;
    }

    public function startFromCurrent(): self
    {
        $this->startFrom = -1;

        return $this;
    }

    public function withMaxSubscriberCountOf(int $count): self
    {
        if ($count < 0) {
            throw new InvalidArgumentException('Max subscriber count cannot be negative');
        }

        $this->maxSubscriberCount = $count;

        return $this;
    }

    public function withNamedConsumerStrategy(string $namedConsumerStrategy): self
    {
        $this->namedConsumerStrategy = $namedConsumerStrategy;

        return $this;
    }

    public function build(): PersistentSubscriptionSettings
    {
        return new PersistentSubscriptionSettings(
            $this->resolveLinkTos,
            $this->startFrom,
            $this->extraStatistics,
            $this->messageTimeoutMilliseconds,
            $this->bufferSize,
            $this->liveBufferSize,
            $this->maxRetryCount,
            $this->readBatchSize,
            $this->checkPointAfterMilliseconds,
            $this->minCheckPointCount,
            $this->maxCheckPointCount,
            $this->maxSubscriberCount,
            $this->namedConsumerStrategy
        );
    }
}
