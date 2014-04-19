<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStore\EventSourcing;

use Prooph\EventStore\EventSourcing\Exception\IdentifierPropertyNotFoundException;
use Prooph\EventStore\EventSourcing\Exception\NoHandlerFoundException;
use Prooph\EventStore\LifeCycleEvent\DetermineEventHandler;
use Prooph\EventStore\LifeCycleEvent\GetIdentifierProperty;
use Prooph\EventStore\Mapping\OnEventNameHandlerStrategy;
use Zend\EventManager\EventManager;

/**
 * EventSourcedAggregateRoot
 * 
 * @author Alexander Miertsch <contact@prooph.de>
 *
 * @package Prooph\EventStore\EventSourcing
 */
abstract class EventSourcedAggregateRoot
{
    /**
     * Current version
     * 
     * @var float 
     */
    protected $version = 0;
    
    /**
     * List of events that are not committed to the EventStore
     * 
     * @var AggregateChangedEvent[]
     */
    protected $pendingEvents = array();

    /**
     * @var EventManager
     */
    protected $lifeCycleEvents;
    
    /**    
     * @param mixed $aggregateId
     * @param array $historyEvents
     */
    protected function initializeFromHistory($aggregateId, array $historyEvents)
    {
        $result = $this->getLifeCycleEvents()->trigger(new GetIdentifierProperty($this));
        $identifierProp = $result->last();
        $this->$identifierProp = $aggregateId;

        $this->replay($historyEvents);
    }

    /**
     * Get pending events
     * 
     * @return AggregateChangedEvent[]
     */
    protected function getPendingEvents()
    {
        $pendingEvents = $this->pendingEvents;
        
        $this->pendingEvents = array();
        
        return $pendingEvents;
    }
    
    /**
     * Replay past events
     * 
     * @param AggregateChangedEvent[] $historyEvents
     * 
     * @return void
     */
    protected function replay(array $historyEvents)
    {
        foreach ($historyEvents as $pastEvent) {
            $handler = $this->getEventHandlerMethod($pastEvent);
            
            $this->{$handler}($pastEvent);
            
            $this->version = $pastEvent->version();
        }
    }

    /**
     * Apply given event
     *
     * @param AggregateChangedEvent $e
     *
     */
    protected function apply(AggregateChangedEvent $e)
    {
        $handler = $this->getEventHandlerMethod($e);
        
        $this->{$handler}($e);

        $this->version += 1;

        $eventRef = new \ReflectionClass($e);

        $versionProp = $eventRef->getProperty('version');

        $versionProp->setAccessible(true);

        $versionProp->setValue($e, $this->version);

        $this->pendingEvents[] = $e;
    }

    /**
     * @return EventManager
     */
    protected function getLifeCycleEvents()
    {
        if (is_null($this->lifeCycleEvents)) {
            $this->lifeCycleEvents = new EventManager(array(
                'EventSourcedAggregateRoot',
                get_class($this)
            ));

            $this->lifeCycleEvents->attachAggregate(new OnEventNameHandlerStrategy());

            $this->lifeCycleEvents->attach(
                GetIdentifierProperty::NAME,
                function(GetIdentifierProperty $e) {
                    if (property_exists($e->getTarget(), 'id')) {
                        return 'id';
                    }

                    throw new IdentifierPropertyNotFoundException(
                        sprintf(
                            'Identifier property of aggregate %s could not be determined. You should register a lister for the %s event!',
                            get_class($e->getTarget()),
                            GetIdentifierProperty::NAME
                        )
                    );
                },
                -100
            );
        }

        return $this->lifeCycleEvents;
    }

    /**
     * @param AggregateChangedEvent $anAggregateChangedEvent
     * @return string
     * @throws Exception\NoHandlerFoundException
     */
    protected function getEventHandlerMethod(AggregateChangedEvent $anAggregateChangedEvent)
    {
        $result = $this->getLifeCycleEvents()->triggerUntil(
            new DetermineEventHandler($this, $anAggregateChangedEvent),
            function ($handlerMethod) {
                return is_string($handlerMethod);
            }
        );

        if (!$result->stopped() || ! method_exists($this, $result->last())) {
            throw new NoHandlerFoundException(
                sprintf(
                    "Can not determine appropriate event handler method of aggregate %s for event %s",
                    get_class($this),
                    get_class($anAggregateChangedEvent)
                )
            );
        }

        return $result->last();
    }
}
