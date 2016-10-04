<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Aggregate;

use Assert\Assertion;
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
     * @var string
     */
    private $versionMethodName = 'getVersion';

    /**
     * @var string
     */
    private $identifierMethodName = 'getId';

    /**
     * @var string
     */
    private $popRecordedEventsMethodName = 'popRecordedEvents';

    /**
     * @var string
     */
    private $replayEventsMethodName = 'replay';

    /**
     * @var string
     */
    private $staticReconstituteFromHistoryMethodName = 'reconstituteFromHistory';

    /**
     * @var null|callable
     */
    private $eventToMessageCallback = null;

    /**
     * @var null|callable
     */
    private $messageToEventCallback = null;

    /**
     * @param null|string   $identifierMethodName
     * @param null|string   $versionMethodName
     * @param null|string   $popRecordedEventsMethodName
     * @param null|string   $replayEventsMethodsName
     * @param null|string   $staticReconstituteFromHistoryMethodName
     * @param null|callable $eventToMessageCallback
     * @param null|callable $messageToEventCallback
     */
    public function __construct(
        $identifierMethodName = null,
        $versionMethodName = null,
        $popRecordedEventsMethodName = null,
        $replayEventsMethodsName = null,
        $staticReconstituteFromHistoryMethodName = null,
        $eventToMessageCallback = null,
        $messageToEventCallback = null)
    {
        if (null !== $identifierMethodName) {
            Assertion::minLength($identifierMethodName, 1, 'Identifier method name needs to be a non empty string');
            $this->identifierMethodName = $identifierMethodName;
        }

        if (null !== $versionMethodName) {
            Assertion::minLength($versionMethodName, 1, 'Version method name needs to be a non empty string');
            $this->versionMethodName = $versionMethodName;
        }

        if (null !== $popRecordedEventsMethodName) {
            Assertion::minLength($popRecordedEventsMethodName, 1, 'Pop recorded events method name needs to be a non empty string');
            $this->popRecordedEventsMethodName = $popRecordedEventsMethodName;
        }

        if (null !== $replayEventsMethodsName) {
            Assertion::minLength($replayEventsMethodsName, 1, 'Replay events method name needs to be a non empty string');
            $this->replayEventsMethodName = $replayEventsMethodsName;
        }

        if (null !== $staticReconstituteFromHistoryMethodName) {
            Assertion::minLength($staticReconstituteFromHistoryMethodName, 1, 'Method name for static method reconstitute from history needs to be non empty string');
            $this->staticReconstituteFromHistoryMethodName = $staticReconstituteFromHistoryMethodName;
        }

        if (null !== $eventToMessageCallback) {
            Assertion::true(is_callable($eventToMessageCallback), 'EventToMessage callback needs to be a callable');
            $this->eventToMessageCallback = $eventToMessageCallback;
        }

        if (null !== $messageToEventCallback) {
            Assertion::true(is_callable($messageToEventCallback), 'MessageToEvent callback needs to be a callable');
            $this->messageToEventCallback = $messageToEventCallback;
        }
    }

    /**
     * @param object $eventSourcedAggregateRoot
     * @throws Exception\AggregateTranslationFailedException
     * @return string
     */
    public function extractAggregateId($eventSourcedAggregateRoot)
    {
        if (! method_exists($eventSourcedAggregateRoot, $this->identifierMethodName)) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Required method %s does not exist for aggregate %s',
                    $this->identifierMethodName,
                    get_class($eventSourcedAggregateRoot)
                )
            );
        }

        return (string)$eventSourcedAggregateRoot->{$this->identifierMethodName}();
    }

    /**
     * @param object $eventSourcedAggregateRoot
     * @return int
     */
    public function extractAggregateVersion($eventSourcedAggregateRoot)
    {
        if (! method_exists($eventSourcedAggregateRoot, $this->versionMethodName)) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Required method %s does not exist for aggregate %s',
                    $this->versionMethodName,
                    get_class($eventSourcedAggregateRoot)
                )
            );
        }

        return (int) $eventSourcedAggregateRoot->{$this->versionMethodName}();
    }

    /**
     * @param AggregateType $aggregateType
     * @param Iterator $historyEvents
     * @throws Exception\AggregateTranslationFailedException
     * @return object reconstructed EventSourcedAggregateRoot
     */
    public function reconstituteAggregateFromHistory(AggregateType $aggregateType, Iterator $historyEvents)
    {
        if ($this->messageToEventCallback) {
            $historyEvents = new MapIterator($historyEvents, $this->messageToEventCallback);
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

        if (! method_exists($aggregateClass, $this->staticReconstituteFromHistoryMethodName)) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Cannot reconstitute aggregate of type %s. Class is missing a static %s method!',
                    $aggregateClass,
                    $this->staticReconstituteFromHistoryMethodName
                )
            );
        }

        $method = $this->staticReconstituteFromHistoryMethodName;

        $aggregate = $aggregateClass::$method($historyEvents);

        if (! $aggregate instanceof $aggregateClass) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Failed to reconstitute aggregate of type %s. Static method %s does not return an instance of the aggregate type!',
                    $aggregateClass,
                    $this->staticReconstituteFromHistoryMethodName
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

        if (! method_exists($eventSourcedAggregateRoot, $this->popRecordedEventsMethodName)) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Can not pop recorded events from aggregate root %s. The AR is missing a method with name %s!',
                    get_class($eventSourcedAggregateRoot),
                    $this->popRecordedEventsMethodName
                )
            );
        }

        $recordedEvents = $eventSourcedAggregateRoot->{$this->popRecordedEventsMethodName}();

        if (! is_array($recordedEvents) && ! $recordedEvents instanceof \Traversable) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Failed to pop recorded events from aggregate root %s. The AR method %s returned a non traversable result!',
                    get_class($eventSourcedAggregateRoot),
                    $this->popRecordedEventsMethodName
                )
            );
        }

        $callback = $this->eventToMessageCallback;

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

        if (! method_exists($eventSourcedAggregateRoot, $this->replayEventsMethodName)) {
            throw new AggregateTranslationFailedException(
                sprintf(
                    'Can not replay events to aggregate root %s. The AR is missing a method with name %s!',
                    get_class($eventSourcedAggregateRoot),
                    $this->replayEventsMethodName
                )
            );
        }

        $callback = $this->messageToEventCallback;

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

            $eventSourcedAggregateRoot->{$this->replayEventsMethodName}($event);
        }
    }
}
