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

namespace Prooph\EventStore;

class EventReadResult
{
    private EventReadStatus $status;
    private string $stream;
    private int $eventNumber;
    private ?ResolvedEvent $event;

    /** @internal */
    public function __construct(EventReadStatus $status, string $stream, int $eventNumber, ?ResolvedEvent $event)
    {
        $this->status = $status;
        $this->stream = $stream;
        $this->eventNumber = $eventNumber;
        $this->event = $event;
    }

    public function status(): EventReadStatus
    {
        return $this->status;
    }

    public function stream(): string
    {
        return $this->stream;
    }

    public function eventNumber(): int
    {
        return $this->eventNumber;
    }

    public function event(): ?ResolvedEvent
    {
        return $this->event;
    }
}
