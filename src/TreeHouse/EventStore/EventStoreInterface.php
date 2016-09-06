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
     * @param EventStreamInterface|Event[] $eventStream
     *
     * @throws EventStoreException
     */
    public function append(EventStreamInterface $eventStream);
}
