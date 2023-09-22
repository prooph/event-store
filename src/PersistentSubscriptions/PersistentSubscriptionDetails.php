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

    /** @psalm-mutation-free */
    public function config(): ?PersistentSubscriptionConfigDetails
    {
        return $this->config;
    }

    /**
     * @return list<PersistentSubscriptionConnectionDetails>
     * @psalm-mutation-free
     */
    public function connections(): array
    {
        return $this->connections;
    }

    /** @psalm-mutation-free */
    public function eventStreamId(): string
    {
        return $this->eventStreamId;
    }

    /** @psalm-mutation-free */
    public function groupName(): string
    {
        return $this->groupName;
    }

    /** @psalm-mutation-free */
    public function status(): string
    {
        return $this->status;
    }

    /** @psalm-mutation-free */
    public function averageItemsPerSecond(): float
    {
        return $this->averageItemsPerSecond;
    }

    /** @psalm-mutation-free */
    public function totalItemsProcessed(): int
    {
        return $this->totalItemsProcessed;
    }

    /** @psalm-mutation-free */
    public function countSinceLastMeasurement(): int
    {
        return $this->countSinceLastMeasurement;
    }

    /** @psalm-mutation-free */
    public function lastProcessedEventNumber(): int
    {
        return $this->lastProcessedEventNumber;
    }

    /** @psalm-mutation-free */
    public function lastKnownEventNumber(): int
    {
        return $this->lastKnownEventNumber;
    }

    /** @psalm-mutation-free */
    public function readBufferCount(): int
    {
        return $this->readBufferCount;
    }

    /** @psalm-mutation-free */
    public function liveBufferCount(): int
    {
        return $this->liveBufferCount;
    }

    /** @psalm-mutation-free */
    public function retryBufferCount(): int
    {
        return $this->retryBufferCount;
    }

    /** @psalm-mutation-free */
    public function totalInFlightMessages(): int
    {
        return $this->totalInFlightMessages;
    }

    /** @psalm-mutation-free */
    public function connectionCount(): int
    {
        return $this->connectionCount;
    }

    /** @psalm-mutation-free */
    public function parkedMessageUri(): string
    {
        return $this->parkedMessageUri;
    }

    /** @psalm-mutation-free */
    public function getMessagesUri(): string
    {
        return $this->getMessagesUri;
    }
}
