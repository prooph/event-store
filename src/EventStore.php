<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStore;

use Assert\Assertion;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Adapter\Adapter;
use Prooph\EventStore\Adapter\Feature\CanHandleTransaction;
use Prooph\EventStore\Exception\StreamNotFoundException;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;

/**
 * EventStore 
 *
 * @author Alexander Miertsch <contact@prooph.de>
 * @package Prooph\EventStore
 */
class EventStore 
{
    /**
     *
     * @var Adapter
     */
    protected $adapter;

    /**
     * @var ActionEventEmitter
     */
    protected $actionEventEmitter;

    /**
     * @var Message[]
     */
    protected $recordedEvents = [];

    /**
     * @var int
     */
    protected $transactionLevel = 0;

    /**
     * Constructor
     *
     * @param Adapter $adapter
     * @param ActionEventEmitter $actionEventEmitter
     */
    public function __construct(Adapter $adapter, ActionEventEmitter $actionEventEmitter)
    {
        $this->adapter = $adapter;
        $this->actionEventEmitter = $actionEventEmitter;
    }

    /**
     * Get the active EventStoreAdapter
     * 
     * @return Adapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @return Message[]
     */
    public function getRecordedEvents()
    {
        return $this->recordedEvents;
    }

    /**
     * @param Stream $stream
     * @throws Exception\RuntimeException
     * @return void
     */
    public function create(Stream $stream)
    {
        $argv = array('stream' => $stream);

        $event = $this->actionEventEmitter->getNewActionEvent(__FUNCTION__ . '.pre', $this, $argv);

        $this->actionEventEmitter->dispatch($event);

        if ($event->propagationIsStopped()) {
            return;
        }

        if ($this->transactionLevel === 0) {
            throw new RuntimeException('Stream creation failed. EventStore is not in an active transaction');
        }

        $stream = $event->getParam('stream');

        $this->adapter->create($stream);

        $this->recordedEvents = array_merge($this->recordedEvents, $stream->streamEvents());

        $event->setName(__FUNCTION__ . '.post');

        $this->actionEventEmitter->dispatch($event);
    }

    /**
     * @param StreamName $streamName
     * @param Message[] $streamEvents
     * @throws Exception\RuntimeException
     * @return void
     */
    public function appendTo(StreamName $streamName, array $streamEvents)
    {
        foreach ($streamEvents as $streamEvent) {
            Assertion::isInstanceOf($streamEvent, Message::class);
        }

        $argv = array('streamName' => $streamName, 'streamEvents' => $streamEvents);

        $event = $this->actionEventEmitter->getNewActionEvent(__FUNCTION__ . '.pre', $this, $argv);

        $this->getActionEventEmitter()->dispatch($event);

        if ($event->propagationIsStopped()) {
            return;
        }

        if ($this->transactionLevel === 0) {
            throw new RuntimeException('Append events to stream failed. EventStore is not in an active transaction');
        }

        $streamName = $event->getParam('streamName');
        $streamEvents = $event->getParam('streamEvents');

        $this->adapter->appendTo($streamName, $streamEvents);

        $this->recordedEvents = array_merge($this->recordedEvents, $streamEvents);

        $event->setName(__FUNCTION__, '.post');

        $this->getActionEventEmitter()->dispatch($event);
    }

    /**
     * @param StreamName $streamName
     * @param null|int $minVersion
     * @throws Exception\StreamNotFoundException
     * @return Stream
     */
    public function load(StreamName $streamName, $minVersion = null)
    {
        $argv = array('streamName' => $streamName, 'minVersion' => $minVersion);

        $event = $this->actionEventEmitter->getNewActionEvent(__FUNCTION__ . '.pre', $this, $argv);

        $this->getActionEventEmitter()->dispatch($event);

        if ($event->propagationIsStopped()) {

            $stream = $event->getParam('stream', false);

            if ($stream instanceof Stream && $stream->streamName()->toString() == $streamName->toString()) {
                return $stream;
            }

            throw new StreamNotFoundException(
                sprintf(
                    'A stream with name %s could not be found',
                    $streamName->toString()
                )
            );
        }

        $streamName = $event->getParam('streamName');

        $minVersion = $event->getParam('minVersion');

        $stream = $this->adapter->load($streamName, $minVersion);

        if (! $stream) {
            throw new StreamNotFoundException(
                sprintf(
                    'A stream with name %s could not be found',
                    $streamName->toString()
                )
            );
        }

        $event->setName(__FUNCTION__, '.post');

        $event->setParam('stream', $stream);

        $this->getActionEventEmitter()->dispatch($event);

        if ($event->propagationIsStopped()) {
            throw new StreamNotFoundException(
                sprintf(
                    'A stream with name %s could not be found',
                    $streamName->toString()
                )
            );
        }

        return $event->getParam('stream');
    }

    /**
     * @param StreamName $streamName
     * @param array $metadata
     * @param null|int $minVersion
     * @return Message[]
     */
    public function loadEventsByMetadataFrom(StreamName $streamName, array $metadata, $minVersion = null)
    {
        $argv = array('streamName' => $streamName, 'metadata' => $metadata, 'minVersion' => $minVersion);

        $event = $this->actionEventEmitter->getNewActionEvent(__FUNCTION__ . '.pre', $this, $argv);

        $this->getActionEventEmitter()->dispatch($event);

        if ($event->propagationIsStopped()) {
            return $event->getParam('streamEvents', array());
        }

        $streamName = $event->getParam('streamName');
        $metadata = $event->getParam('metadata');
        $minVersion = $event->getParam('minVersion');

        $events = $this->adapter->loadEventsByMetadataFrom($streamName, $metadata, $minVersion);

        $event->setName(__FUNCTION__, '.post');

        $event->setParam('streamEvents', $events);

        $this->getActionEventEmitter()->dispatch($event);

        if ($event->propagationIsStopped()) {
            return array();
        }

        return $event->getParam('streamEvents');
    }

    /**
     * Begin transaction
     *
     * @triggers beginTransaction
     */
    public function beginTransaction()
    {
        if ($this->transactionLevel === 0 && $this->adapter instanceof CanHandleTransaction) {
            $this->adapter->beginTransaction();
        }

        if ($this->transactionLevel > 0) {
            trigger_error("Nesting transactions is deprecated in prooph/event-store v5. Please align your transaction handling.", E_USER_DEPRECATED);
        }

        $this->transactionLevel++;

        $event = $this->actionEventEmitter->getNewActionEvent(
            __FUNCTION__,
            $this,
            ['isNestedTransaction' => $this->transactionLevel > 1, 'transactionLevel' => $this->transactionLevel]
        );

        $this->getActionEventEmitter()->dispatch($event);
    }

    /**
     * Commit transaction
     *
     * @triggers commit.pre  On every commit call. If a listener stops propagation, the ES performs a rollback
     * @triggers commit.post Once after all started transactions are committed. Event includes all "recordedEvents".
     *                       Perfect to attach a domain event dispatcher
     */
    public function commit()
    {
        if ($this->transactionLevel === 0) {
            throw new RuntimeException('Cannot commit transaction. EventStore has no active transaction');
        }

        $event = $this->getActionEventEmitter()->getNewActionEvent(__FUNCTION__ . '.pre', $this);

        $event->setParam('isNestedTransaction', $this->transactionLevel > 1);
        $event->setParam('transactionLevel', $this->transactionLevel);

        $this->getActionEventEmitter()->dispatch($event);

        if ($event->propagationIsStopped()) {
            $this->rollback();
            return;
        }

        $this->transactionLevel--;

        //Nested transaction commit only decreases transaction level
        if ($this->transactionLevel > 0) return;

        if ($this->adapter instanceof CanHandleTransaction) {
            $this->adapter->commit();
        }

        $event = $this->getActionEventEmitter()->getNewActionEvent(__FUNCTION__ . '.post', $this, ['recordedEvents' => $this->recordedEvents]);

        $this->recordedEvents = [];

        $this->getActionEventEmitter()->dispatch($event);
    }

    /**
     * Rollback transaction
     *
     * @triggers rollback
     */
    public function rollback()
    {
        if ($this->transactionLevel === 0) {
            throw new RuntimeException('Cannot rollback transaction. EventStore has no active transaction');
        }

        if ($this->adapter instanceof CanHandleTransaction) {
            $this->adapter->rollback();
        }

        $this->transactionLevel = 0;

        $event = $this->actionEventEmitter->getNewActionEvent(__FUNCTION__, $this);

        $this->actionEventEmitter->dispatch($event);

        $this->recordedEvents = [];
    }

    /**
     * @return ActionEventEmitter
     */
    public function getActionEventEmitter()
    {
        return $this->actionEventEmitter;
    }
}
