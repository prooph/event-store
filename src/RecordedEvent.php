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

namespace Prooph\EventStore;

use DateTimeImmutable;

/** @psalm-immutable */
class RecordedEvent
{
    protected string $eventStreamId;

    protected int $eventNumber;

    protected EventId $eventId;

    protected string $eventType;

    protected bool $isJson;

    protected string $data;

    protected string $metadata;

    protected DateTimeImmutable $created;

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

    /** @psalm-mutation-free */
    public function eventStreamId(): string
    {
        return $this->eventStreamId;
    }

    /** @psalm-mutation-free */
    public function eventNumber(): int
    {
        return $this->eventNumber;
    }

    /** @psalm-mutation-free */
    public function eventId(): EventId
    {
        return $this->eventId;
    }

    /** @psalm-mutation-free */
    public function eventType(): string
    {
        return $this->eventType;
    }

    /** @psalm-mutation-free */
    public function isJson(): bool
    {
        return $this->isJson;
    }

    /** @psalm-mutation-free */
    public function data(): string
    {
        return $this->data;
    }

    /** @psalm-mutation-free */
    public function metadata(): string
    {
        return $this->metadata;
    }

    /** @psalm-mutation-free */
    public function created(): DateTimeImmutable
    {
        return $this->created;
    }
}
