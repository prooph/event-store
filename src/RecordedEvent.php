<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2019 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use DateTimeImmutable;

class RecordedEvent
{
    /** @var string */
    protected $eventStreamId;
    /** @var int */
    protected $eventNumber;
    /** @var EventId */
    protected $eventId;
    /** @var string */
    protected $eventType;
    /** @var bool */
    protected $isJson;
    /** @var string */
    protected $data;
    /** @var string */
    protected $metadata;
    /** @var DateTimeImmutable */
    protected $created;

    /** @internal */
    public function __construct(
        string $eventStreamId,
        int $eventNumber,
        EventId $eventId,
        string $eventType,
        bool $isJson,
        string $data,
        string $metadata,
        DateTimeImmutable $created
    ) {
        $this->eventStreamId = $eventStreamId;
        $this->eventNumber = $eventNumber;
        $this->eventId = $eventId;
        $this->eventType = $eventType;
        $this->isJson = $isJson;
        $this->data = $data;
        $this->metadata = $metadata;
        $this->created = $created;
    }

    public function eventStreamId(): string
    {
        return $this->eventStreamId;
    }

    public function eventNumber(): int
    {
        return $this->eventNumber;
    }

    public function eventId(): EventId
    {
        return $this->eventId;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function isJson(): bool
    {
        return $this->isJson;
    }

    public function data(): string
    {
        return $this->data;
    }

    public function metadata(): string
    {
        return $this->metadata;
    }

    public function created(): DateTimeImmutable
    {
        return $this->created;
    }
}
