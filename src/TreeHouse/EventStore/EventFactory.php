<?php

namespace TreeHouse\EventStore;

use TreeHouse\Serialization\SerializerInterface;

class EventFactory
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * EventFactory constructor.
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param SerializedEvent $event
     *
     * @return Event
     */
    public function createFromSerializedEvent(SerializedEvent $event)
    {
        return new Event(
            $event->getId(),
            $event->getName(),
            $this->serializer->deserialize(
                $event->getName(),
                $event->getPayload()
            ),
            $event->getPayloadVersion(),
            $event->getVersion(),
            $event->getDate()
        );
    }
}
