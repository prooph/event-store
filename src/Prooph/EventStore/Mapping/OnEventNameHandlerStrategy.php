<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 17.04.14 - 22:11
 */

namespace Prooph\EventStore\Mapping;

use Prooph\EventStore\EventSourcing\AggregateChangedEvent;
use Prooph\EventStore\LifeCycleEvent\DetermineEventHandler;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;

/**
 * Class OnEventNameHandlerStrategy
 *
 * @package Prooph\EventStore\Mapping
 * @author Alexander Miertsch <contact@prooph.de>
 */
class OnEventNameHandlerStrategy extends AbstractListenerAggregate
{
    /**
     * Attach one or more listeners
     *
     * Implementors may add an optional $priority argument; the EventManager
     * implementation will pass this to the aggregate.
     *
     * @param EventManagerInterface $events
     *
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(DetermineEventHandler::NAME, array($this, 'onDetermineEventHandler'), -100);
    }

    /**
     * @param DetermineEventHandler $anEvent
     * @return string
     */
    public function onDetermineEventHandler(DetermineEventHandler $anEvent)
    {
        $eventName = $this->determineEventName($anEvent->getAggregateChangedEvent());

        return 'on' . $eventName;
    }

    /**
     * Determine event name
     *
     * @param AggregateChangedEvent $e
     *
     * @return string
     */
    protected function determineEventName(AggregateChangedEvent $e)
    {
        return join('', array_slice(explode('\\', get_class($e)), -1));
    }
}
 