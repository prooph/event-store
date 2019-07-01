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
final class PersistentSubscriptionConnectionDetails
{
    /** @var string */
    private $from;
    /** @var string */
    private $username;
    /** @var float */
    private $averageItemsPerSecond;
    /** @var int */
    private $totalItemsProcessed;
    /** @var int */
    private $countSinceLastMeasurement;
    /** @var int */
    private $availableSlots;
    /** @var int */
    private $inFlightMessages;

    private function __construct()
    {
    }

    public static function fromArray(array $data): self
    {
        $details = new self();

        $details->from = $data['from'];
        $details->username = $data['username'];
        $details->averageItemsPerSecond = $data['averageItemsPerSecond'];
        $details->totalItemsProcessed = $data['totalItemsProcessed'];
        $details->countSinceLastMeasurement = $data['countSinceLastMeasurement'];
        $details->availableSlots = $data['availableSlots'];
        $details->inFlightMessages = $data['inFlightMessages'];

        return $details;
    }

    public function from(): string
    {
        return $this->from;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function averageItemsPerSecond(): float
    {
        return $this->averageItemsPerSecond;
    }

    public function totalItemsProcessed(): int
    {
        return $this->totalItemsProcessed;
    }

    public function countSinceLastMeasurement(): int
    {
        return $this->countSinceLastMeasurement;
    }

    public function availableSlots(): int
    {
        return $this->availableSlots;
    }

    public function inFlightMessages(): int
    {
        return $this->inFlightMessages;
    }
}
