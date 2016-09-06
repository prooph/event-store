<?php

namespace TreeHouse\EventStore;

class InMemoryEventStore implements EventStoreInterface
{
    /**
     * @var array
     */
    private $store = [];

    /**
     * @inheritdoc
     */
    public function getStream($id)
    {
        if (isset($this->store[$id])) {
            return new EventStream(
                $this->store[$id]
            );
        }

        throw new EventStreamNotFoundException($id);
    }

    /**
     * @inheritdoc
     *
     * @throws DuplicateVersionException
     */
    public function append(EventStreamInterface $eventStream)
    {
        /** @var $event Event */
        foreach ($eventStream as $event) {
            $version = $event->getVersion();
            $id = $event->getId();

            if (!isset($this->store[$id])) {
                $this->store[$id] = [];
            }

            if (isset($this->store[$id][$version])) {
                throw new DuplicateVersionException($version);
            }

            $this->store[$id][$version] = $event;
        }
    }
}
