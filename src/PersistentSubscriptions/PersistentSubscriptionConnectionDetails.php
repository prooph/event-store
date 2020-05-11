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

/**
 * @internal
 * @psalm-immutable
 */
final class PersistentSubscriptionConnectionDetails
{
    private string $from;
    private string $username;
    private float $averageItemsPerSecond;
    private int $totalItemsProcessed;
    private int $countSinceLastMeasurement;
    private int $availableSlots;
    private int $inFlightMessages;

    private function __construct(
        string $from,
        string $username,
        float $averageItemsPerSecond,
        int $totalItemsProcessed,
        int $countSinceLastMeasurement,
        int $availableSlots,
        int $inFlightMessages
    ) {
        $this->from = $from;
        $this->username = $username;
        $this->averageItemsPerSecond = $averageItemsPerSecond;
        $this->totalItemsProcessed = $totalItemsProcessed;
        $this->countSinceLastMeasurement = $countSinceLastMeasurement;
        $this->availableSlots = $availableSlots;
        $this->inFlightMessages = $inFlightMessages;
    }

    /**
     * @param array<string, string|float|int> $data
     * @psalm-pure
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['from'],
            (string) $data['username'],
            (float) $data['averageItemsPerSecond'],
            (int) $data['totalItemsProcessed'],
            (int) $data['countSinceLastMeasurement'],
            (int) $data['availableSlots'],
            (int) $data['inFlightMessages'],
        );
    }

    /** @psalm-pure */
    public function from(): string
    {
        return $this->from;
    }

    /** @psalm-pure */
    public function username(): string
    {
        return $this->username;
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
    public function availableSlots(): int
    {
        return $this->availableSlots;
    }

    /** @psalm-pure */
    public function inFlightMessages(): int
    {
        return $this->inFlightMessages;
    }
}
