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

use Assert\Assertion;

class AggregateTranslatorConfiguration
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

    public function __construct(
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
    
    public static function createWithDefaults()
    {
        return new self(
            'getVersion',
            'getId',
            'popRecordedEvents',
            'replay',
            'reconstituteFromHistory'
        );
    }

    public function withVersionMethodName($versionMethodName)
    {
        Assertion::minLength($versionMethodName, 1, 'Version method name needs to be a non empty string');

        $instance = clone $this;
        $instance->versionMethodName = $versionMethodName;
        return $instance;
    }

    public function withIdentifierMethodName($identifierMethodName)
    {
        Assertion::minLength($identifierMethodName, 1, 'Identifier method name needs to be a non empty string');

        $instance = clone $this;
        $instance->identifierMethodName = $identifierMethodName;
        return $instance;
    }

    public function withPopRecordedEventsMethodName($popRecordedEventsMethodName)
    {
        Assertion::minLength($popRecordedEventsMethodName, 1, 'Pop recorded events method name needs to be a non empty string');

        $instance = clone $this;
        $instance->popRecordedEventsMethodName = $popRecordedEventsMethodName;
        return $instance;
    }

    public function withReplayEventsMethodName($replayEventsMethodName)
    {
        Assertion::minLength($replayEventsMethodName, 1, 'Replay events method name needs to be a non empty string');

        $instance = clone $this;
        $instance->replayEventsMethodName = $replayEventsMethodName;
        return $instance;
    }

    public function withStaticReconstituteFromHistoryMethodName($staticReconstituteFromHistoryMethodName)
    {
        Assertion::minLength($staticReconstituteFromHistoryMethodName, 1, 'Method name for static method reconstitute from history needs to be non empty string');

        $instance = clone $this;
        $instance->staticReconstituteFromHistoryMethodName = $staticReconstituteFromHistoryMethodName;
        return $instance;
    }

    public function withEventToMessageCallback($eventToMessageCallback)
    {
        Assertion::true(is_callable($eventToMessageCallback), 'EventToMessage callback needs to be a callable');

        $instance = clone $this;
        $instance->eventToMessageCallback = $eventToMessageCallback;
        return $instance;
    }

    public function withMessageToEventCallback($messageToEventCallback)
    {
        Assertion::true(is_callable($messageToEventCallback), 'MessageToEvent callback needs to be a callable');

        $instance = clone $this;
        $instance->messageToEventCallback = $messageToEventCallback;
        return $instance;
    }

    public function versionMethodName()
    {
        return $this->versionMethodName;
    }

    public function identifierMethodName()
    {
        return $this->identifierMethodName;
    }

    public function popRecordedEventsMethodName()
    {
        return $this->popRecordedEventsMethodName;
    }

    public function replayEventsMethodName()
    {
        return $this->replayEventsMethodName;
    }

    public function staticReconstituteFromHistoryMethodName()
    {
        return $this->staticReconstituteFromHistoryMethodName;
    }

    public function eventToMessageCallback()
    {
        return $this->eventToMessageCallback;
    }

    public function messageToEventCallback()
    {
        return $this->messageToEventCallback;
    }
}