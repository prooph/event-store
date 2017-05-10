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
     */
    public function getPartialStream($id, $fromVersion, $toVersion = null)
    {
        if (isset($this->store[$id])) {
            $events = array_filter($this->store[$id], function (Event $event) use ($fromVersion, $toVersion) {
                return ($event->getVersion() > $fromVersion && (!$toVersion || $event->getVersion() <= $toVersion));
            });

            return new EventStream(array_values($events));
        }

        return new EventStream([]);
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
