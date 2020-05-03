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

namespace Prooph\EventStore\PersistentSubscriptions;

/** @psalm-immutable */
final class PersistentSubscriptionDetails
{
    /**
     *
     * Only populated when retrieved via PersistentSubscriptionsManager::describe() method.
     */
    private PersistentSubscriptionConfigDetails $config;
    /**
     * @var array<int, PersistentSubscriptionConnectionDetails>
     *
     * Only populated when retrieved via PersistentSubscriptionsManager::describe() method.
     */
    private array $connections = [];
    private string $eventStreamId;
    private string $groupName;
    private string $status;
    private float $averageItemsPerSecond;
    private int $totalItemsProcessed;
    private int $countSinceLastMeasurement;
    private int $lastProcessedEventNumber;
    private int $lastKnownEventNumber;
    private int $readBufferCount;
    private int $liveBufferCount;
    private int $retryBufferCount;
    private int $totalInFlightMessages;
    private int $connectionCount;
    private string $parkedMessageUri;
    private string $getMessagesUri;

    private function __construct()
    {
    }

    public static function fromArray(array $data): self
    {
        $details = new self();

        if (isset($data['config'])) {
            $details->config = PersistentSubscriptionConfigDetails::fromArray($data['config']);
        }

        if (isset($data['connections'])) {
            foreach ($data['connections'] as $connection) {
                $details->connections[] = PersistentSubscriptionConnectionDetails::fromArray($connection);
            }
        }

        $details->eventStreamId = $data['eventStreamId'];
        $details->groupName = $data['groupName'];
        $details->status = $data['status'];
        $details->averageItemsPerSecond = $data['averageItemsPerSecond'];
        $details->totalItemsProcessed = $data['totalItemsProcessed'];
        $details->countSinceLastMeasurement = $data['countSinceLastMeasurement'] ?? 0;
        $details->lastProcessedEventNumber = $data['lastProcessedEventNumber'];
        $details->lastKnownEventNumber = $data['lastKnownEventNumber'];
        $details->readBufferCount = $data['readBufferCount'] ?? 0;
        $details->liveBufferCount = $data['liveBufferCount'] ?? 0;
        $details->retryBufferCount = $data['retryBufferCount'] ?? 0;
        $details->totalInFlightMessages = $data['totalInFlightMessages'];
        $details->parkedMessageUri = $data['parkedMessageUri'];
        $details->getMessagesUri = $data['getMessagesUri'];
        $details->connectionCount = $data['connectionCount'] ?? 0;

        return $details;
    }

    /** @psalm-pure */
    public function config(): PersistentSubscriptionConfigDetails
    {
        return $this->config;
    }

    /**
     * @return array<int, PersistentSubscriptionConnectionDetails>
     * @psalm-pure
     */
    public function connections(): array
    {
        return $this->connections;
    }

    /** @psalm-pure */
    public function eventStreamId(): string
    {
        return $this->eventStreamId;
    }

    /** @psalm-pure */
    public function groupName(): string
    {
        return $this->groupName;
    }

    /** @psalm-pure */
    public function status(): string
    {
        return $this->status;
    }

    /** @psalm-pure */
    public function averageItemsPerSecond(): float
    {
        return $this->averageItemsPerSecond;
    }

    /** @psalm-pure */
    public function totalItemsProcessed(): int
    {
        return $this->totalItemsProcessed;
    }

    /** @psalm-pure */
    public function countSinceLastMeasurement(): int
    {
        return $this->countSinceLastMeasurement;
    }

    /** @psalm-pure */
    public function lastProcessedEventNumber(): int
    {
        return $this->lastProcessedEventNumber;
    }

    /** @psalm-pure */
    public function lastKnownEventNumber(): int
    {
        return $this->lastKnownEventNumber;
    }

    /** @psalm-pure */
    public function readBufferCount(): int
    {
        return $this->readBufferCount;
    }

    /** @psalm-pure */
    public function liveBufferCount(): int
    {
        return $this->liveBufferCount;
    }

    /** @psalm-pure */
    public function retryBufferCount(): int
    {
        return $this->retryBufferCount;
    }

    /** @psalm-pure */
    public function totalInFlightMessages(): int
    {
        return $this->totalInFlightMessages;
    }

    /** @psalm-pure */
    public function connectionCount(): int
    {
        return $this->connectionCount;
    }

    /** @psalm-pure */
    public function parkedMessageUri(): string
    {
        return $this->parkedMessageUri;
    }

    /** @psalm-pure */
    public function getMessagesUri(): string
    {
        return $this->getMessagesUri;
    }
}
