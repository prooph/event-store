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

namespace Prooph\EventStore\PersistentSubscriptions;

/** @internal */
final class PersistentSubscriptionConfigDetails
{
    /** @var bool */
    private $resolveLinktos;
    /** @var int */
    private $startFrom;
    /** @var int */
    private $messageTimeoutMilliseconds;
    /** @var bool */
    private $extraStatistics;
    /** @var int */
    private $maxRetryCount;
    /** @var int */
    private $liveBufferSize;
    /** @var int */
    private $bufferSize;
    /** @var int */
    private $readBatchSize;
    /** @var int */
    private $checkPointAfterMilliseconds;
    /** @var int */
    private $minCheckPointCount;
    /** @var int */
    private $maxCheckPointCount;
    /** @var int */
    private $maxSubscriberCount;
    /** @var string */
    private $namedConsumerStrategy;
    /** @var bool */
    private $preferRoundRobin;

    private function __construct()
    {
    }

    public static function fromArray(array $data): self
    {
        $details = new self();

        $details->resolveLinktos = $data['resolveLinktos'];
        $details->startFrom = $data['startFrom'];
        $details->messageTimeoutMilliseconds = $data['messageTimeoutMilliseconds'];
        $details->extraStatistics = $data['extraStatistics'];
        $details->maxRetryCount = $data['maxRetryCount'];
        $details->liveBufferSize = $data['liveBufferSize'];
        $details->bufferSize = $data['bufferSize'];
        $details->readBatchSize = $data['readBatchSize'];
        $details->checkPointAfterMilliseconds = $data['checkPointAfterMilliseconds'];
        $details->minCheckPointCount = $data['minCheckPointCount'];
        $details->maxCheckPointCount = $data['maxCheckPointCount'];
        $details->maxSubscriberCount = $data['maxSubscriberCount'];
        $details->namedConsumerStrategy = $data['namedConsumerStrategy'];
        $details->preferRoundRobin = $data['preferRoundRobin'];

        return $details;
    }

    public function resolveLinktos(): bool
    {
        return $this->resolveLinktos;
    }

    public function startFrom(): int
    {
        return $this->startFrom;
    }

    public function messageTimeoutMilliseconds(): int
    {
        return $this->messageTimeoutMilliseconds;
    }

    public function extraStatistics(): bool
    {
        return $this->extraStatistics;
    }

    public function maxRetryCount(): int
    {
        return $this->maxRetryCount;
    }

    public function liveBufferSize(): int
    {
        return $this->liveBufferSize;
    }

    public function bufferSize(): int
    {
        return $this->bufferSize;
    }

    public function readBatchSize(): int
    {
        return $this->readBatchSize;
    }

    public function checkPointAfterMilliseconds(): int
    {
        return $this->checkPointAfterMilliseconds;
    }

    public function minCheckPointCount(): int
    {
        return $this->minCheckPointCount;
    }

    public function maxCheckPointCount(): int
    {
        return $this->maxCheckPointCount;
    }

    public function maxSubscriberCount(): int
    {
        return $this->maxSubscriberCount;
    }

    public function namedConsumerStrategy(): string
    {
        return $this->namedConsumerStrategy;
    }

    public function preferRoundRobin(): bool
    {
        return $this->preferRoundRobin;
    }
}
