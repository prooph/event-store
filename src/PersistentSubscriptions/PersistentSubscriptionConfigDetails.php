<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2022 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2022 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\PersistentSubscriptions;

/**
 * @internal
 * @psalm-immutable
 */
final class PersistentSubscriptionConfigDetails
{
    private bool $resolveLinktos;

    private int $startFrom;

    private int $messageTimeoutMilliseconds;

    private bool $extraStatistics;

    private int $maxRetryCount;

    private int $liveBufferSize;

    private int $bufferSize;

    private int $readBatchSize;

    private int $checkPointAfterMilliseconds;

    private int $minCheckPointCount;

    private int $maxCheckPointCount;

    private int $maxSubscriberCount;

    private string $namedConsumerStrategy;

    private bool $preferRoundRobin;

    private function __construct(
        bool $resolveLinktos,
        int $startFrom,
        int $messageTimeoutMilliseconds,
        bool $extraStatistics,
        int $maxRetryCount,
        int $liveBufferSize,
        int $bufferSize,
        int $readBatchSize,
        int $checkPointAfterMilliseconds,
        int $minCheckPointCount,
        int $maxCheckPointCount,
        int $maxSubscriberCount,
        string $namedConsumerStrategy,
        bool $preferRoundRobin
    ) {
        $this->resolveLinktos = $resolveLinktos;
        $this->startFrom = $startFrom;
        $this->messageTimeoutMilliseconds = $messageTimeoutMilliseconds;
        $this->extraStatistics = $extraStatistics;
        $this->maxRetryCount = $maxRetryCount;
        $this->liveBufferSize = $liveBufferSize;
        $this->bufferSize = $bufferSize;
        $this->readBatchSize = $readBatchSize;
        $this->checkPointAfterMilliseconds = $checkPointAfterMilliseconds;
        $this->minCheckPointCount = $minCheckPointCount;
        $this->maxCheckPointCount = $maxCheckPointCount;
        $this->maxSubscriberCount = $maxSubscriberCount;
        $this->namedConsumerStrategy = $namedConsumerStrategy;
        $this->preferRoundRobin = $preferRoundRobin;
    }

    /**
     * @param array<string, bool|int|string> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (bool) $data['resolveLinktos'],
            (int) $data['startFrom'],
            (int) $data['messageTimeoutMilliseconds'],
            (bool) $data['extraStatistics'],
            (int) $data['maxRetryCount'],
            (int) $data['liveBufferSize'],
            (int) $data['bufferSize'],
            (int) $data['readBatchSize'],
            (int) $data['checkPointAfterMilliseconds'],
            (int) $data['minCheckPointCount'],
            (int) $data['maxCheckPointCount'],
            (int) $data['maxSubscriberCount'],
            (string) $data['namedConsumerStrategy'],
            (bool) $data['preferRoundRobin']
        );
    }

    /** @psalm-mutation-free */
    public function resolveLinktos(): bool
    {
        return $this->resolveLinktos;
    }

    /** @psalm-mutation-free */
    public function startFrom(): int
    {
        return $this->startFrom;
    }

    /** @psalm-mutation-free */
    public function messageTimeoutMilliseconds(): int
    {
        return $this->messageTimeoutMilliseconds;
    }

    /** @psalm-mutation-free */
    public function extraStatistics(): bool
    {
        return $this->extraStatistics;
    }

    /** @psalm-mutation-free */
    public function maxRetryCount(): int
    {
        return $this->maxRetryCount;
    }

    /** @psalm-mutation-free */
    public function liveBufferSize(): int
    {
        return $this->liveBufferSize;
    }

    /** @psalm-mutation-free */
    public function bufferSize(): int
    {
        return $this->bufferSize;
    }

    /** @psalm-mutation-free */
    public function readBatchSize(): int
    {
        return $this->readBatchSize;
    }

    /** @psalm-mutation-free */
    public function checkPointAfterMilliseconds(): int
    {
        return $this->checkPointAfterMilliseconds;
    }

    /** @psalm-mutation-free */
    public function minCheckPointCount(): int
    {
        return $this->minCheckPointCount;
    }

    /** @psalm-mutation-free */
    public function maxCheckPointCount(): int
    {
        return $this->maxCheckPointCount;
    }

    /** @psalm-mutation-free */
    public function maxSubscriberCount(): int
    {
        return $this->maxSubscriberCount;
    }

    /** @psalm-mutation-free */
    public function namedConsumerStrategy(): string
    {
        return $this->namedConsumerStrategy;
    }

    /** @psalm-mutation-free */
    public function preferRoundRobin(): bool
    {
        return $this->preferRoundRobin;
    }
}
