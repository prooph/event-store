<?php

namespace TreeHouse\EventStore\Upcasting;

use TreeHouse\EventStore\SerializedEvent;

class SimpleUpcasterChain implements UpcasterInterface
{
    /**
     * @var UpcasterInterface[]
     */
    protected $upcasters = [];

    /**
     * @param UpcasterInterface $upcaster
     */
    public function registerUpcaster(UpcasterInterface $upcaster)
    {
        $this->upcasters[] = $upcaster;
    }

    /**
     * Upcasts via a chain of upcasters.
     *
     * @inheritdoc
     */
    public function upcast(SerializedEvent $event, UpcastingContext $context)
    {
        foreach ($this->upcasters as $upcaster) {
            if ($upcaster->supports($event)) {
                $event = $upcaster->upcast($event, $context);
            }
        }

        return $event;
    }

    /**
     * @inheritdoc
     */
    public function supports(SerializedEvent $event)
    {
        foreach ($this->upcasters as $upcaster) {
            if ($upcaster->supports($event)) {
                return true;
            }
        }

        return false;
    }
}
