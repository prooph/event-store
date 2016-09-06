<?php

namespace TreeHouse\EventStore;

use TreeHouse\Serialization\SerializableInterface;

interface MutableEventStoreInterface extends EventStoreInterface
{
    /**
     * @param $aggregateId
     * @param $version
     */
    public function remove($aggregateId, $version);

    /**
     * @param $aggregateId
     * @param $version
     * @param Event[] $events
     */
    public function insertBefore($aggregateId, $version, array $events);

    /**
     * @param Event                 $originalEvent
     * @param SerializableInterface $payload
     */
    public function updateEventPayload(Event $originalEvent, SerializableInterface $payload);
}
