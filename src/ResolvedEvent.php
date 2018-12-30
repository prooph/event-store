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

use Prooph\EventStore\Internal\ResolvedEvent as InternalResolvedEvent;

/**
 * A structure representing a single event or a resolved link event.
 */
class ResolvedEvent implements InternalResolvedEvent
{
    /**
     * The event, or the resolved link event if this is a link event
     * @var RecordedEvent|null
     */
    private $event;
    /**
     * The link event if this ResolvedEvent is a link event.
     * @var RecordedEvent|null
     */
    private $link;
    /**
     * Returns the event that was read or which triggered the subscription.
     *
     * If this ResolvedEvent represents a link event, the Link
     * will be the OriginalEvent otherwise it will be the event.
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
