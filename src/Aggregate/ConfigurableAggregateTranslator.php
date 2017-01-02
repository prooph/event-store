<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\EventStore\Aggregate;

use Iterator;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Aggregate\Exception\AggregateTranslationFailedException;
use Prooph\EventStore\Util\MapIterator;

/**
 * Class ConfigurableAggregateTranslator
 *
 * @package Prooph\EventStore\Aggregate
 * @author Alexander Miertsch <contact@prooph.de>
 */
class ConfigurableAggregateTranslator implements AggregateTranslator
{
    /**
     * @var AggregateTranslatorConfiguration
     */
    private $configuration;

    /**
     * @param null|AggregateTranslatorConfiguration $configuration
     */
    public function __construct(AggregateTranslatorConfiguration $configuration = null)
    {
        $this->configuration = $configuration ?: AggregateTranslatorConfiguration::createWithDefaults();
    }

    /**
     * @param object $eventSourcedAggregateRoot
     * @throws Exception\AggregateTranslationFailedException
     * @return string
     */
    public function extractAggregateId($eventSourcedAggregateRoot)
    {
        $identifierMethodName = $this->configuration->identifierMethodName();

        if (! method_exists($eventSourcedAggregateRoot, $identifierMethodName)) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Required method %s does not exist for aggregate %s',
                    $identifierMethodName,
                    get_class($eventSourcedAggregateRoot)
                )
            );
        }

        return (string)$eventSourcedAggregateRoot->{$identifierMethodName}();
    }

    /**
     * @param object $eventSourcedAggregateRoot
     * @return int
     */
    public function extractAggregateVersion($eventSourcedAggregateRoot)
    {
        $versionMethodName = $this->configuration->versionMethodName();

        if (! method_exists($eventSourcedAggregateRoot, $versionMethodName)) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Required method %s does not exist for aggregate %s',
                    $versionMethodName,
                    get_class($eventSourcedAggregateRoot)
                )
            );
        }

        return (int) $eventSourcedAggregateRoot->{$versionMethodName}();
    }

    /**
     * @param AggregateType $aggregateType
     * @param Iterator $historyEvents
     * @throws Exception\AggregateTranslationFailedException
     * @return object reconstructed EventSourcedAggregateRoot
     */
    public function reconstituteAggregateFromHistory(AggregateType $aggregateType, Iterator $historyEvents)
    {
        $messageToEventCallback = $this->configuration->messageToEventCallback();

        if ($messageToEventCallback) {
            $historyEvents = new MapIterator($historyEvents, $messageToEventCallback);
        }

        $aggregateClass = $aggregateType->toString();

        if (! class_exists($aggregateClass)) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Can not reconstitute aggregate of type %s. Class was not found',
                    $aggregateClass
                )
            );
        }

        $staticReconstituteFromHistoryMethodName = $this->configuration->staticReconstituteFromHistoryMethodName();

        if (! method_exists($aggregateClass, $staticReconstituteFromHistoryMethodName)) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Cannot reconstitute aggregate of type %s. Class is missing a static %s method!',
                    $aggregateClass,
                    $staticReconstituteFromHistoryMethodName
                )
            );
        }

        $method = $staticReconstituteFromHistoryMethodName;

        $aggregate = $aggregateClass::$method($historyEvents);

        if (! $aggregate instanceof $aggregateClass) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Failed to reconstitute aggregate of type %s. Static method %s does not return an instance of the aggregate type!',
                    $aggregateClass,
                    $staticReconstituteFromHistoryMethodName
                )
            );
        }

        return $aggregate;
    }

    /**
     * @param object $eventSourcedAggregateRoot
     * @throws Exception\AggregateTranslationFailedException
     * @return Message[]
     */
    public function extractPendingStreamEvents($eventSourcedAggregateRoot)
    {
        if (! is_object($eventSourcedAggregateRoot)) {
            throw new AggregateTranslationFailedException('Event sourced Aggregate Root needs to be an object. Got ' . gettype($eventSourcedAggregateRoot));
        }

        $popRecordedEventsMethodName = $this->configuration->popRecordedEventsMethodName();
        
        if (! method_exists($eventSourcedAggregateRoot, $popRecordedEventsMethodName)) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Can not pop recorded events from aggregate root %s. The AR is missing a method with name %s!',
                    get_class($eventSourcedAggregateRoot),
                    $popRecordedEventsMethodName
                )
            );
        }

        $recordedEvents = $eventSourcedAggregateRoot->{$popRecordedEventsMethodName}();

        if (! is_array($recordedEvents) && ! $recordedEvents instanceof \Traversable) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Failed to pop recorded events from aggregate root %s. The AR method %s returned a non traversable result!',
                    get_class($eventSourcedAggregateRoot),
                    $popRecordedEventsMethodName
                )
            );
        }

        $callback = $this->configuration->eventToMessageCallback();

        foreach ($recordedEvents as $i => $recordedEvent) {
            if ($callback) {
                $recordedEvent = $callback($recordedEvent);
                $recordedEvents[$i] = $recordedEvent;
            }

            if (! $recordedEvent instanceof Message) {
                throw new AggregateTranslationFailedException(sprintf(
                    'A recorded event of the aggregate root %s has the wrong type. Expected Prooph\Common\Messaging\Message. Got %s',
                    get_class($eventSourcedAggregateRoot),
                    is_object($recordedEvent)? get_class($recordedEvent) : gettype($recordedEvent)
                ));
            }
        }

        return $recordedEvents;
    }

    /**
     * @param object $eventSourcedAggregateRoot
     * @param Iterator $events
     * @throws Exception\AggregateTranslationFailedException
     */
    public function replayStreamEvents($eventSourcedAggregateRoot, Iterator $events)
    {
        if (! is_object($eventSourcedAggregateRoot)) {
            throw new AggregateTranslationFailedException('Event sourced Aggregate Root needs to be an object. Got ' . gettype($eventSourcedAggregateRoot));
        }

        $replayEventsMethodName = $this->configuration->replayEventsMethodName();
        if (! method_exists($eventSourcedAggregateRoot, $replayEventsMethodName)) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Can not replay events to aggregate root %s. The AR is missing a method with name %s!',
                    get_class($eventSourcedAggregateRoot),
                    $replayEventsMethodName
                )
            );
        }

        $callback = $this->configuration->messageToEventCallback();

        foreach ($events as $event) {
            if (! $event instanceof Message) {
                throw new AggregateTranslationFailedException(sprintf(
                    'Cannot replay event %s. Expected instance of Prooph\Common\Messaging\Message.',
                    is_object($event)? get_class($event) : gettype($event)
                ));
            }

            if ($callback) {
                $event = $callback($event);
            }

            $eventSourcedAggregateRoot->{$replayEventsMethodName}($event);
        }
    }
}
