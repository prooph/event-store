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
     * @var string
     */
    private $versionMethodName;

    /**
     * @var string
     */
    private $identifierMethodName;

    /**
     * @var string
     */
    private $popRecordedEventsMethodName;

    /**
     * @var string
     */
    private $replayEventsMethodName;

    /**
     * @var string
     */
    private $staticReconstituteFromHistoryMethodName;

    /**
     * @var null|callable
     */
    private $eventToMessageCallback;

    /**
     * @var null|callable
     */
    private $messageToEventCallback;

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
        $config = AggregateTranslatorConfiguration::createWithDefaults();

        if (null !== $identifierMethodName) {
            $config = $config->withIdentifierMethodName($identifierMethodName);
        }

        if (null !== $versionMethodName) {
            $config = $config->withVersionMethodName($versionMethodName);
        }

        if (null !== $popRecordedEventsMethodName) {
            $config = $config->withPopRecordedEventsMethodName($popRecordedEventsMethodName);
        }

        if (null !== $replayEventsMethodsName) {
            $config = $config->withReplayEventsMethodName($replayEventsMethodsName);
        }

        if (null !== $staticReconstituteFromHistoryMethodName) {
            $config = $config->withStaticReconstituteFromHistoryMethodName($staticReconstituteFromHistoryMethodName);
        }

        if (null !== $eventToMessageCallback) {
            $config = $config->withEventToMessageCallback($eventToMessageCallback);
        }

        if (null !== $messageToEventCallback) {
            $config = $config->withMessageToEventCallback($messageToEventCallback);
        }

        $this->identifierMethodName = $config->identifierMethodName();
        $this->versionMethodName = $config->versionMethodName();
        $this->popRecordedEventsMethodName = $config->popRecordedEventsMethodName();
        $this->replayEventsMethodName = $config->replayEventsMethodName();
        $this->staticReconstituteFromHistoryMethodName = $config->staticReconstituteFromHistoryMethodName();
        $this->eventToMessageCallback = $config->eventToMessageCallback();
        $this->messageToEventCallback = $config->messageToEventCallback();
    }

    /**
     * @param null|AggregateTranslatorConfiguration $configuration
     * @return ConfigurableAggregateTranslator
     */
    public static function fromConfiguration(AggregateTranslatorConfiguration $configuration = null)
    {
        $config = $configuration ?: AggregateTranslatorConfiguration::createWithDefaults();

        return new self(
            $config->versionMethodName(),
            $config->identifierMethodName(),
            $config->popRecordedEventsMethodName(),
            $config->replayEventsMethodName(),
            $config->staticReconstituteFromHistoryMethodName(),
            $config->eventToMessageCallback(),
            $config->messageToEventCallback()
        );
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
