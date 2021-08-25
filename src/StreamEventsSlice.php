<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

/** @psalm-immutable */
class StreamEventsSlice
{
    private SliceReadStatus $status;
    private string $stream;
    private int $fromEventNumber;
    private ReadDirection $readDirection;
    /** @var list<ResolvedEvent> */
    private array $events;
    private int $nextEventNumber;
    private int $lastEventNumber;
    private bool $isEndOfStream;

    /**
     * @internal
     *
     * @param list<ResolvedEvent> $events
     */
    public function __construct(
        SliceReadStatus $status,
        string $stream,
        int $fromEventNumber,
        ReadDirection $readDirection,
        array $events,
        int $nextEventNumber,
        int $lastEventNumber,
        bool $isEndOfStream
    ) {
        $this->status = $status;
        $this->stream = $stream;
        $this->fromEventNumber = $fromEventNumber;
        $this->readDirection = $readDirection;
        $this->events = $events;
        $this->nextEventNumber = $nextEventNumber;
        $this->lastEventNumber = $lastEventNumber;
        $this->isEndOfStream = $isEndOfStream;
    }

    /** @psalm-mutation-free */
    public function status(): SliceReadStatus
    {
        return $this->status;
    }

    /** @psalm-mutation-free */
    public function stream(): string
    {
        return $this->stream;
    }

    /** @psalm-mutation-free */
    public function fromEventNumber(): int
    {
        return $this->fromEventNumber;
    }

    /** @psalm-mutation-free */
    public function readDirection(): ReadDirection
    {
        return $this->readDirection;
    }

    /**
     * @return list<ResolvedEvent>
     *
     * @psalm-mutation-free
     */
    public function events(): array
    {
        return $this->events;
    }

    /** @psalm-mutation-free */
    public function nextEventNumber(): int
    {
        return $this->nextEventNumber;
    }

    /** @psalm-mutation-free */
    public function lastEventNumber(): int
    {
        return $this->lastEventNumber;
    }

    /** @psalm-mutation-free */
    public function isEndOfStream(): bool
    {
        return $this->isEndOfStream;
    }
}
