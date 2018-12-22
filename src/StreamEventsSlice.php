<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

class StreamEventsSlice
{
    /** @var SliceReadStatus */
    private $status;
    /** @var string */
    private $stream;
    /** @var int */
    private $fromEventNumber;
    /** @var ReadDirection */
    private $readDirection;
    /** @var ResolvedEvent[] */
    private $events;
    /** @var int */
    private $nextEventNumber;
    /** @var int */
    private $lastEventNumber;
    /** @var bool */
    private $isEndOfStream;

    /** @internal */
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

    public function status(): SliceReadStatus
    {
        return $this->status;
    }

    public function stream(): string
    {
        return $this->stream;
    }

    public function fromEventNumber(): int
    {
        return $this->fromEventNumber;
    }

    public function readDirection(): ReadDirection
    {
        return $this->readDirection;
    }

    /**
     * @return ResolvedEvent[]
     */
    public function events(): array
    {
        return $this->events;
    }

    public function nextEventNumber(): int
    {
        return $this->nextEventNumber;
    }

    public function lastEventNumber(): int
    {
        return $this->lastEventNumber;
    }

    public function isEndOfStream(): bool
    {
        return $this->isEndOfStream;
    }
}
