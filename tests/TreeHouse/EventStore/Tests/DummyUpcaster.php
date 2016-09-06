<?php

namespace TreeHouse\EventStore\Tests;

use TreeHouse\EventStore\SerializedEvent;
use TreeHouse\EventStore\Upcasting\UpcasterInterface;
use TreeHouse\EventStore\Upcasting\UpcastingContext;

class DummyUpcaster implements UpcasterInterface
{
    /**
     * @var callable
     */
    protected $eventMatcher;

    /**
     * @var SerializedEvent
     */
    protected $returnValue;

    /**
     * @param callable        $eventMatcher
     * @param SerializedEvent $returnValue
     */
    public function __construct(callable $eventMatcher, SerializedEvent $returnValue)
    {
        $this->eventMatcher = $eventMatcher;
        $this->returnValue = $returnValue;
    }

    /**
     * @inheritdoc
     */
    public function upcast(SerializedEvent $event, UpcastingContext $context)
    {
        return $this->returnValue;
    }

    /**
     * @inheritdoc
     */
    public function supports(SerializedEvent $event)
    {
        $matcher = $this->eventMatcher;

        if ($matcher($event)) {
            return true;
        }

        return false;
    }
}
