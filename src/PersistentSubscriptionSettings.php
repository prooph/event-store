<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2020 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use JsonSerializable;
use Prooph\EventStore\Exception\InvalidArgumentException;
use stdClass;

class PersistentSubscriptionSettings implements JsonSerializable
{
    /**
     * Tells the subscription to resolve link events.
     */
    private bool $resolveLinkTos;
    /**
     * Start the subscription from the position-of the event in the stream.
     * If the value is set to `-1` that the subscription should start from
     * where the stream is when the subscription is first connected.
     */
    private int $startFrom;
    /**
     * Tells the backend to measure timings on the clients so statistics will contain histograms of them.
     */
    private bool $extraStatistics;
    /**
     * The amount of time the system should try to checkpoint after.
     */
    private int $checkPointAfterMilliseconds;
    /**
     * The size of the live buffer (in memory) before resorting to paging.
     */
    private int $liveBufferSize;
    /**
     * The size of the read batch when in paging mode.
     */
    private int $readBatchSize;
    /**
     * The number of messages that should be buffered when in paging mode.
     */
    private int $bufferSize;
    /**
     * The maximum number of messages not checkpointed before forcing a checkpoint.
     */
    private int $maxCheckPointCount;
    /**
     * Sets the number of times a message should be retried before considered a bad message.
     */
    private int $maxRetryCount;
    /**
     * Sets the maximum number of allowed subscribers.
     */
    private int $maxSubscriberCount;
    /**
     * Sets the timeout for a client before the message will be retried.
     */
    private int $messageTimeoutMilliseconds;
    /**
     * The minimum number of messages to write a checkpoint for.
     */
    private int $minCheckPointCount;
    private string $namedConsumerStrategy;

    private const INT_32_MAX = 2147483647;

    public static function default(): self
    {
        return self::create()->build();
    }

    public static function create(): PersistentSubscriptionSettingsBuilder
    {
        return new PersistentSubscriptionSettingsBuilder();
    }

    /** @internal  */
    public function __construct(
        bool $resolveLinkTos,
        int $startFrom,
        bool $extraStatistics,
        int $messageTimeoutMilliseconds,
        int $bufferSize,
        int $liveBufferSize,
        int $maxRetryCount,
        int $readBatchSize,
        int $checkPointAfterMilliseconds,
        int $minCheckPointCount,
        int $maxCheckPointCount,
        int $maxSubscriberCount,
        string $namedConsumerStrategy
    ) {
        if ($checkPointAfterMilliseconds > self::INT_32_MAX) {
            throw new InvalidArgumentException('checkPointAfterMilliseconds must smaller than ' . self::INT_32_MAX);
        }

        if ($messageTimeoutMilliseconds > self::INT_32_MAX) {
            throw new InvalidArgumentException('messageTimeoutMilliseconds must smaller than ' . self::INT_32_MAX);
        }

        $this->resolveLinkTos = $resolveLinkTos;
        $this->startFrom = $startFrom;
        $this->extraStatistics = $extraStatistics;
        $this->checkPointAfterMilliseconds = $checkPointAfterMilliseconds;
        $this->liveBufferSize = $liveBufferSize;
        $this->readBatchSize = $readBatchSize;
        $this->bufferSize = $bufferSize;
        $this->maxCheckPointCount = $maxCheckPointCount;
        $this->maxRetryCount = $maxRetryCount;
        $this->maxSubscriberCount = $maxSubscriberCount;
        $this->messageTimeoutMilliseconds = $messageTimeoutMilliseconds;
        $this->minCheckPointCount = $minCheckPointCount;
        $this->namedConsumerStrategy = $namedConsumerStrategy;
    }

    public function resolveLinkTos(): bool
    {
        return $this->resolveLinkTos;
    }

    public function startFrom(): int
    {
        return $this->startFrom;
    }

    public function extraStatistics(): bool
    {
        return $this->extraStatistics;
    }

    public function checkPointAfterMilliseconds(): int
    {
        return $this->checkPointAfterMilliseconds;
    }

    public function liveBufferSize(): int
    {
        return $this->liveBufferSize;
    }

    public function readBatchSize(): int
    {
        return $this->readBatchSize;
    }

    public function bufferSize(): int
    {
        return $this->bufferSize;
    }

    public function maxCheckPointCount(): int
    {
        return $this->maxCheckPointCount;
    }

    public function maxRetryCount(): int
    {
        return $this->maxRetryCount;
    }

    public function maxSubscriberCount(): int
    {
        return $this->maxSubscriberCount;
    }

    public function messageTimeoutMilliseconds(): int
    {
        return $this->messageTimeoutMilliseconds;
    }

    public function minCheckPointCount(): int
    {
        return $this->minCheckPointCount;
    }

    public function namedConsumerStrategy(): string
    {
        return $this->namedConsumerStrategy;
    }

    public function jsonSerialize(): object
    {
        $object = new stdClass();
        $object->resolveLinkTos = $this->resolveLinkTos;
        $object->startFrom = $this->startFrom;
        $object->extraStatistics = $this->extraStatistics;
        $object->checkPointAfterMilliseconds = $this->checkPointAfterMilliseconds;
        $object->liveBufferSize = $this->liveBufferSize;
        $object->readBatchSize = $this->readBatchSize;
        $object->bufferSize = $this->bufferSize;
        $object->maxCheckPointCount = $this->maxCheckPointCount;
        $object->maxRetryCount = $this->maxRetryCount;
        $object->maxSubscriberCount = $this->maxSubscriberCount;
        $object->messageTimeoutMilliseconds = $this->messageTimeoutMilliseconds;
        $object->minCheckPointCount = $this->minCheckPointCount;
        $object->namedConsumerStrategy = $this->namedConsumerStrategy;

        return $object;
    }
}
