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

namespace Prooph\EventStore;

use Prooph\EventStore\Internal\ResolvedEvent as InternalResolvedEvent;

/**
 * A structure representing a single event or a resolved link event.
 */
class ResolvedEvent implements InternalResolvedEvent
{
    /**
     * If this ResolvedEvent is a link, this will contain the linked event.
     * If this ResolvedEvent is a simple event without link, the event will be here.
     * @var RecordedEvent|null
     */
    private $event;
    /**
     * If this ResolvedEvent is a link, this will contain the link. Otherwise it will be empty.
     * @var RecordedEvent|null
     */
    private $link;
    /**
     * Returns the event that was read or which triggered the subscription.
     * If this ResolvedEvent is a link, this will contain the link. Otherwise it will be the event.
     * @var RecordedEvent|null
     */
    private $originalEvent;
    /**
     * Indicates whether this ResolvedEvent is a resolved link event.
     * @var bool
     */
    private $isResolved;
    /**
     * The logical position of the OriginalEvent
     * @var Position|null
     */
    private $originalPosition;

    /** @internal */
    public function __construct(?RecordedEvent $event, ?RecordedEvent $link, ?Position $originalPosition)
    {
        $this->event = $event;
        $this->link = $link;
        $this->originalEvent = $link ?? $event;
        $this->isResolved = null !== $link && null !== $event;
        $this->originalPosition = $originalPosition;
    }

    public function event(): ?RecordedEvent
    {
        return $this->event;
    }

    public function link(): ?RecordedEvent
    {
        return $this->link;
    }

    public function originalEvent(): ?RecordedEvent
    {
        return $this->originalEvent;
    }

    public function isResolved(): bool
    {
        return $this->isResolved;
    }

    public function originalPosition(): ?Position
    {
        return $this->originalPosition;
    }

    public function originalStreamName(): string
    {
        return $this->originalEvent->eventStreamId();
    }

    public function originalEventNumber(): int
    {
        return $this->originalEvent->eventNumber();
    }
}
