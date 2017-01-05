<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\EventStore\Aggregate;

use Assert\Assertion;

class AggregateTranslatorConfiguration
{
    const DEFAULT_VERSION_METHOD_NAME = 'getVersion';
    const DEFAULT_IDENTIFIER_METHOD_NAME = 'getId';
    const DEFAULT_POP_RECORDED_EVENTS_METHOD_NAME = 'popRecordedEvents';
    const DEFAULT_REPLAY_EVENTS_METHOD_NAME = 'replay';
    const DEFAULT_STATIC_RECONSTITUTE_FROM_HISTORY_METHOD_NAME = 'reconstituteFromHistory';
    const DEFAULT_EVENT_TO_MESSAGE_CALLBACK = null;
    const DEFAULT_MESSAGE_TO_EVENT_CALLBACK = null;

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
     * AggregateTranslatorConfiguration constructor.
     *
     * @param string $versionMethodName
     * @param string $identifierMethodName
     * @param string $popRecordedEventsMethodName
     * @param string $replayEventsMethodName
     * @param string $staticReconstituteFromHistoryMethodName
     * @param null|callable $eventToMessageCallback
     * @param null|callable $messageToEventCallback
     */
    private function __construct(
        $versionMethodName,
        $identifierMethodName,
        $popRecordedEventsMethodName,
        $replayEventsMethodName,
        $staticReconstituteFromHistoryMethodName,
        $eventToMessageCallback = null,
        $messageToEventCallback = null
    ) {
        $this->versionMethodName = $versionMethodName;
        $this->identifierMethodName = $identifierMethodName;
        $this->popRecordedEventsMethodName = $popRecordedEventsMethodName;
        $this->replayEventsMethodName = $replayEventsMethodName;
        $this->staticReconstituteFromHistoryMethodName = $staticReconstituteFromHistoryMethodName;
        $this->eventToMessageCallback = $eventToMessageCallback;
        $this->messageToEventCallback = $messageToEventCallback;
    }

    /**
     * Creates a configuration instance with default values
     *
     * @return AggregateTranslatorConfiguration
     */
    public static function createWithDefaults()
    {
        return new self(
            self::DEFAULT_VERSION_METHOD_NAME,
            self::DEFAULT_IDENTIFIER_METHOD_NAME,
            self::DEFAULT_POP_RECORDED_EVENTS_METHOD_NAME,
            self::DEFAULT_REPLAY_EVENTS_METHOD_NAME,
            self::DEFAULT_STATIC_RECONSTITUTE_FROM_HISTORY_METHOD_NAME
        );
    }

    /**
     * Returns a new instance having set the given name of the method which is used to determine the version
     *
     * @param string $versionMethodName
     * @return AggregateTranslatorConfiguration
     */
    public function withVersionMethodName($versionMethodName)
    {
        Assertion::minLength($versionMethodName, 1, 'Version method name needs to be a non empty string');

        $instance = clone $this;
        $instance->versionMethodName = $versionMethodName;

        return $instance;
    }

    /**
     * Returns a new instance having set the given name of the method which is used to determine the identifier
     *
     * @param string $identifierMethodName
     * @return AggregateTranslatorConfiguration
     */
    public function withIdentifierMethodName($identifierMethodName)
    {
        Assertion::minLength($identifierMethodName, 1, 'Identifier method name needs to be a non empty string');

        $instance = clone $this;
        $instance->identifierMethodName = $identifierMethodName;

        return $instance;
    }

    /**
     * Returns a new instance having set the given name of the method which is used to pop recorded events
     *
     * @param string $popRecordedEventsMethodName
     * @return AggregateTranslatorConfiguration
     */
    public function withPopRecordedEventsMethodName($popRecordedEventsMethodName)
    {
        Assertion::minLength($popRecordedEventsMethodName, 1, 'Pop recorded events method name needs to be a non empty string');

        $instance = clone $this;
        $instance->popRecordedEventsMethodName = $popRecordedEventsMethodName;

        return $instance;
    }

    /**
     * Returns a new instance having set the given name of the method which is used to replay events
     *
     * @param string $replayEventsMethodName
     * @return AggregateTranslatorConfiguration
     */
    public function withReplayEventsMethodName($replayEventsMethodName)
    {
        Assertion::minLength($replayEventsMethodName, 1, 'Replay events method name needs to be a non empty string');

        $instance = clone $this;
        $instance->replayEventsMethodName = $replayEventsMethodName;

        return $instance;
    }

    /**
     * Returns a new instance having set the given name of the static method which is used reconstitute from history
     *
     * @param string $staticReconstituteFromHistoryMethodName
     * @return AggregateTranslatorConfiguration
     */
    public function withStaticReconstituteFromHistoryMethodName($staticReconstituteFromHistoryMethodName)
    {
        Assertion::minLength($staticReconstituteFromHistoryMethodName, 1, 'Method name for static method reconstitute from history needs to be non empty string');

        $instance = clone $this;
        $instance->staticReconstituteFromHistoryMethodName = $staticReconstituteFromHistoryMethodName;

        return $instance;
    }

    /**
     * Returns a new instance having set the given callback which is used to map events to messages
     *
     * @param callable $eventToMessageCallback
     * @return AggregateTranslatorConfiguration
     */
    public function withEventToMessageCallback($eventToMessageCallback)
    {
        Assertion::true(is_callable($eventToMessageCallback), 'EventToMessage callback needs to be a callable');

        $instance = clone $this;
        $instance->eventToMessageCallback = $eventToMessageCallback;

        return $instance;
    }

    /**
     * Returns a new instance having set the given callback which is used to map messages to events
     *
     * @param callable $messageToEventCallback
     * @return AggregateTranslatorConfiguration
     */
    public function withMessageToEventCallback($messageToEventCallback)
    {
        Assertion::true(is_callable($messageToEventCallback), 'MessageToEvent callback needs to be a callable');

        $instance = clone $this;
        $instance->messageToEventCallback = $messageToEventCallback;

        return $instance;
    }

    /**
     * Returns the name of the method which is used to determine the version
     *
     * @return string
     */
    public function versionMethodName()
    {
        return $this->versionMethodName;
    }

    /**
     * Returns the name of the method which is used to determine the identifier
     *
     * @return string
     */
    public function identifierMethodName()
    {
        return $this->identifierMethodName;
    }

    /**
     * Returns the name of the method which is used to pop recorded events
     *
     * @return string
     */
    public function popRecordedEventsMethodName()
    {
        return $this->popRecordedEventsMethodName;
    }

    /**
     * Returns the name of the method which is used to replay events
     *
     * @return string
     */
    public function replayEventsMethodName()
    {
        return $this->replayEventsMethodName;
    }

    /**
     * Returns the name of the static method which is used to reconstitute from history
     *
     * @return string
     */
    public function staticReconstituteFromHistoryMethodName()
    {
        return $this->staticReconstituteFromHistoryMethodName;
    }

    /**
     * Returns the callback which is used to map events to messages
     *
     * @return callable|null
     */
    public function eventToMessageCallback()
    {
        return $this->eventToMessageCallback;
    }

    /**
     * Returns the callback which is used to map messages to events
     *
     * @return callable|null
     */
    public function messageToEventCallback()
    {
        return $this->messageToEventCallback;
    }
}
