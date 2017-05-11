<?php

namespace TreeHouse\EventStore;

use TreeHouse\EventStore\Encryption\CryptoEventStreamFactory;

class EncryptingEventStore implements EventStoreInterface
{
    /**
     * @var EventStoreInterface
     */
    protected $eventStore;

    /**
     * @var CryptoEventStreamFactory
     */
    private $factory;

    /**
     * @param EventStoreInterface      $eventStore
     * @param CryptoEventStreamFactory $factory
     */
    public function __construct(EventStoreInterface $eventStore, CryptoEventStreamFactory $factory)
    {
        $this->eventStore = $eventStore;
        $this->factory = $factory;
    }

    /**
     * @inheritdoc
     */
    public function getStream($id)
    {
        /** @var Event[]|EventStreamInterface $eventStream */
        $eventStream = $this->eventStore->getStream($id);

        return $this->factory->createDecryptingStream($eventStream);
    }

    /**
     * @inheritdoc
     */
    public function getPartialStream($id, $fromVersion, $toVersion = null)
    {
        /** @var Event[]|EventStreamInterface $eventStream */
        $eventStream = $this->eventStore->getPartialStream($id, $fromVersion, $toVersion);

        return $this->factory->createDecryptingStream($eventStream);
    }

    /**
     * @inheritdoc
     */
    public function append(EventStreamInterface $eventStream)
    {
        $this->eventStore->append(
            $this->factory->createEncryptingStream($eventStream)
        );
    }
}
