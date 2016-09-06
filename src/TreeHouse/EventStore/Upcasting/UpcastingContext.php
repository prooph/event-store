<?php

namespace TreeHouse\EventStore\Upcasting;

use TreeHouse\EventStore\EventStreamInterface;
use TreeHouse\Serialization\SerializerInterface;

class UpcastingContext
{
    /**
     * Contains an event stream for the aggregate up till the point of the event being upcasted.
     *
     * @var EventStreamInterface
     */
    protected $eventStream;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @param EventStreamInterface $eventStream
     * @param SerializerInterface  $serializer
     */
    public function __construct(EventStreamInterface $eventStream, SerializerInterface $serializer)
    {
        $this->eventStream = $eventStream;
        $this->serializer = $serializer;
    }

    /**
     * @return EventStreamInterface
     */
    public function getEventStream()
    {
        return $this->eventStream;
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }
}
