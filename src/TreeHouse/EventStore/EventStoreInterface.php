<?php

namespace TreeHouse\EventStore;

interface EventStoreInterface
{
    /**
     * @param mixed $id
     *
     * @throws EventStreamNotFoundException
     *
     * @return EventStreamInterface<Event>|Event[]
     */
    public function getStream($id);

    /**
     * @param mixed $id
     * @param int $fromVersion
     * @param int|null $toVersion
     *
     * @return EventStreamInterface
     */
    public function getPartialStream($id, $fromVersion, $toVersion = null);

    /**
     * @param EventStreamInterface|Event[] $eventStream
     *
     * @throws EventStoreException
     */
    public function append(EventStreamInterface $eventStream);
}
