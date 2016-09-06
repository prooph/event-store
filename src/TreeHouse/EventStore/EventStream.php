<?php

namespace TreeHouse\EventStore;

use ArrayIterator;
use IteratorAggregate;

class EventStream implements IteratorAggregate, EventStreamInterface
{
    /**
     * @var EventInterface[]
     */
    protected $events = [];

    /**
     * @param EventInterface[] $events
     */
    public function __construct(array $events = [])
    {
        $this->events = $events;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->events);
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->events);
    }
}
