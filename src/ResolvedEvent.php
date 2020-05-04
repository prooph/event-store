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

use Prooph\EventStore\Internal\ResolvedEvent as InternalResolvedEvent;

/**
 * A structure representing a single event or a resolved link event.
 *
 * @psalm-immutable
 */
class ResolvedEvent implements InternalResolvedEvent
{
    /**
     * If this ResolvedEvent is a link, this will contain the linked event.
     * If this ResolvedEvent is a simple event without link, the event will be here.
     */
    private ?RecordedEvent $event;
    /**
     * If this ResolvedEvent is a link, this will contain the link. Otherwise it will be empty.
     */
    private ?RecordedEvent $link;
    /**
     * Returns the event that was read or which triggered the subscription.
     * If this ResolvedEvent is a link, this will contain the link. Otherwise it will be the event.
     */
    private ?RecordedEvent $originalEvent;
    /**
     * Indicates whether this ResolvedEvent is a resolved link event.
     */
    private bool $isResolved;
    /**
     * The logical position of the OriginalEvent
     */
    private ?Position $originalPosition;

    /** @internal */
    public function __construct(?RecordedEvent $event, ?RecordedEvent $link, ?Position $originalPosition)
    {
        $this->event = $event;
        $this->link = $link;
        $this->originalEvent = ($link ?? $event);
        $this->isResolved = (null !== $link && null !== $event);
        $this->originalPosition = $originalPosition;
    }

    /** @psalm-pure */
    public function event(): ?RecordedEvent
    {
        return $this->event;
    }

    /** @psalm-pure */
    public function link(): ?RecordedEvent
    {
        return $this->link;
    }

    /** @psalm-pure */
    public function originalEvent(): ?RecordedEvent
    {
        return $this->originalEvent;
    }

    /** @psalm-pure */
    public function isResolved(): bool
    {
        return $this->isResolved;
    }

    /** @psalm-pure */
    public function originalPosition(): ?Position
    {
        return $this->originalPosition;
    }

    /** @psalm-pure */
    public function originalStreamName(): string
    {
        return null !== $this->originalEvent ? $this->originalEvent->eventStreamId() : '';
    }

    /** @psalm-pure */
    public function originalEventNumber(): int
    {
        return null !== $this->originalEvent ? $this->originalEvent->eventNumber() : 0;
    }
}
