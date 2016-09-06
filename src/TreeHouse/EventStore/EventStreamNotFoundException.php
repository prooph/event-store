<?php

namespace TreeHouse\EventStore;

use RuntimeException;

class EventStreamNotFoundException extends RuntimeException
{
    protected $aggregateId;

    /**
     * @param $aggregateId
     */
    public function __construct($aggregateId)
    {
        $this->aggregateId = $aggregateId;

        parent::__construct(sprintf('Event stream for aggregate with id "%s" not found', $aggregateId));
    }
}
