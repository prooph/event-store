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
    private ?PersistentSubscriptionConfigDetails $config;
    /**
     * @var list<PersistentSubscriptionConnectionDetails>
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

    /**
     * @param list<PersistentSubscriptionConnectionDetails> $connections
     */
    private function __construct(
        ?PersistentSubscriptionConfigDetails $config,
        array $connections,
        string $eventStreamId,
        string $groupName,
        string $status,
        float $averageItemsPerSecond,
        int $totalItemsProcessed,
        int $countSinceLastMeasurement,
        int $lastProcessedEventNumber,
        int $lastKnownEventNumber,
        int $readBufferCount,
        int $liveBufferCount,
        int $retryBufferCount,
        int $totalInFlightMessages,
        int $connectionCount,
        string $parkedMessageUri,
        string $getMessagesUri
    ) {
        $this->config = $config;
        $this->connections = $connections;
        $this->eventStreamId = $eventStreamId;
        $this->groupName = $groupName;
        $this->status = $status;
        $this->averageItemsPerSecond = $averageItemsPerSecond;
        $this->totalItemsProcessed = $totalItemsProcessed;
        $this->countSinceLastMeasurement = $countSinceLastMeasurement;
        $this->lastProcessedEventNumber = $lastProcessedEventNumber;
        $this->lastKnownEventNumber = $lastKnownEventNumber;
        $this->readBufferCount = $readBufferCount;
        $this->liveBufferCount = $liveBufferCount;
        $this->retryBufferCount = $retryBufferCount;
        $this->totalInFlightMessages = $totalInFlightMessages;
        $this->connectionCount = $connectionCount;
        $this->parkedMessageUri = $parkedMessageUri;
        $this->getMessagesUri = $getMessagesUri;
    }

    public static function fromArray(array $data): self
    {
        $config = null;

        if (isset($data['config'])) {
            /** @var array<string, bool|int|string> $data['config'] */
            $config = PersistentSubscriptionConfigDetails::fromArray($data['config']);
        }

        $connections = [];

        if (isset($data['connections'])) {
            /** @var array<string, string|float|int> $connection */
            foreach ($data['connections'] as $connection) {
                $connections[] = PersistentSubscriptionConnectionDetails::fromArray($connection);
            }
        }

        return new self(
            $config,
            $connections,
            (string) $data['eventStreamId'],
            (string) $data['groupName'],
            (string) $data['status'],
            (float) $data['averageItemsPerSecond'],
            (int) $data['totalItemsProcessed'],
            (int) ($data['countSinceLastMeasurement'] ?? 0),
            (int) $data['lastProcessedEventNumber'],
            (int) $data['lastKnownEventNumber'],
            (int) ($data['readBufferCount'] ?? 0),
            (int) ($data['liveBufferCount'] ?? 0),
            (int) ($data['retryBufferCount'] ?? 0),
            (int) $data['totalInFlightMessages'],
            (int) ($data['connectionCount'] ?? 0),
            (string) $data['parkedMessageUri'],
            (string) $data['getMessagesUri']
        );
    }

    /** @psalm-pure */
    public function config(): ?PersistentSubscriptionConfigDetails
    {
        return $this->config;
    }

    /**
     * @return list<PersistentSubscriptionConnectionDetails>
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
