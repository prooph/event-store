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

/**
 * Represents an event to be written.
 */
class EventData
{
    private EventId $eventId;

    private string $eventType;

    private bool $isJson;

    private string $data;

    private string $metaData;

    public function __construct(?EventId $eventId, string $eventType, bool $isJson, string $data = '', string $metaData = '')
    {
        if (null === $eventId) {
            $eventId = EventId::generate();
        }

        $this->eventId = $eventId;
        $this->eventType = $eventType;
        $this->isJson = $isJson;
        $this->data = $data;
        $this->metaData = $metaData;
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

    public function metaData(): string
    {
        return $this->metaData;
    }
}
