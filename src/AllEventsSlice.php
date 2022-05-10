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

class AllEventsSlice
{
    private ReadDirection $readDirection;

    private Position $fromPosition;

    private Position $nextPosition;

    /** @var list<ResolvedEvent> */
    private array $events;

    private bool $isEndOfStream;

    /**
     * @internal
     *
     * @param list<ResolvedEvent> $events
     */
    public function __construct(
        ReadDirection $readDirection,
        Position $fromPosition,
        Position $nextPosition,
        array $events
    ) {
        $this->readDirection = $readDirection;
        $this->fromPosition = $fromPosition;
        $this->nextPosition = $nextPosition;
        $this->events = $events;
        $this->isEndOfStream = \count($events) === 0;
    }

    public function readDirection(): ReadDirection
    {
        return $this->readDirection;
    }

    public function fromPosition(): Position
    {
        return $this->fromPosition;
    }

    public function nextPosition(): Position
    {
        return $this->nextPosition;
    }

    /** @return list<ResolvedEvent> */
    public function events(): array
    {
        return $this->events;
    }

    public function isEndOfStream(): bool
    {
        return $this->isEndOfStream;
    }
}
