<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\EventStore;

use AppendIterator;
use ArrayIterator;
use DateTimeInterface;
use Iterator;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Adapter\Adapter;
use Prooph\EventStore\Adapter\Feature\CanHandleTransaction;
use Prooph\EventStore\Exception\StreamNotFoundException;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use Prooph\EventStore\Util\CompositeIterator;

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
     * @var Iterator
     */
    protected $recordedEvents;

    /**
     * @var bool
     */
    protected $inTransaction = false;

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
        $this->recordedEvents = new ArrayIterator();
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
     * @return Iterator
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
        $argv = ['stream' => $stream];

        $event = $this->actionEventEmitter->getNewActionEvent(__FUNCTION__ . '.pre', $this, $argv);

        $this->actionEventEmitter->dispatch($event);

        if ($event->propagationIsStopped()) {
            return;
        }

        if (!$this->inTransaction) {
            throw new RuntimeException('Stream creation failed. EventStore is not in an active transaction');
        }

        $stream = $event->getParam('stream');

        $this->adapter->create($stream);

        $appendIterator = new AppendIterator();
        $appendIterator->append($this->recordedEvents);
        $appendIterator->append($stream->streamEvents());

        $this->recordedEvents = $appendIterator;

        $event->setName(__FUNCTION__ . '.post');

        $this->actionEventEmitter->dispatch($event);
    }

    /**
     * @param StreamName $streamName
     * @param Iterator $streamEvents
     * @throws Exception\RuntimeException
     * @return void
     */
    public function appendTo(StreamName $streamName, Iterator $streamEvents)
    {
        $argv = ['streamName' => $streamName, 'streamEvents' => $streamEvents];

        $event = $this->actionEventEmitter->getNewActionEvent(__FUNCTION__ . '.pre', $this, $argv);

        $this->getActionEventEmitter()->dispatch($event);

        if ($event->propagationIsStopped()) {
            return;
        }

        if (!$this->inTransaction) {
            throw new RuntimeException('Append events to stream failed. EventStore is not in an active transaction');
        }

        $streamName = $event->getParam('streamName');
        $streamEvents = $event->getParam('streamEvents');

        $this->adapter->appendTo($streamName, $streamEvents);

        $appendIterator = new AppendIterator();
        $appendIterator->append($this->recordedEvents);
        $appendIterator->append($streamEvents);

        $this->recordedEvents = $appendIterator;

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
        $argv = ['streamName' => $streamName, 'minVersion' => $minVersion];

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

        $event->setName(__FUNCTION__ . '.post');

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
     * @return Iterator
     */
    public function loadEventsByMetadataFrom(StreamName $streamName, array $metadata, $minVersion = null)
    {
        $argv = ['streamName' => $streamName, 'metadata' => $metadata, 'minVersion' => $minVersion];

        $event = $this->actionEventEmitter->getNewActionEvent(__FUNCTION__ . '.pre', $this, $argv);

        $this->getActionEventEmitter()->dispatch($event);

        if ($event->propagationIsStopped()) {
            return $event->getParam('streamEvents', []);
        }

        $streamName = $event->getParam('streamName');
        $metadata = $event->getParam('metadata');
        $minVersion = $event->getParam('minVersion');

        $events = $this->adapter->loadEventsByMetadataFrom($streamName, $metadata, $minVersion);

        $event->setName(__FUNCTION__ . '.post');

        $event->setParam('streamEvents', $events);

        $this->getActionEventEmitter()->dispatch($event);

        if ($event->propagationIsStopped()) {
            return [];
        }

        return $event->getParam('streamEvents');
    }

    /**
     * @param StreamName[] $streamNames
     * @param DateTimeInterface|null $since
     * @param array $metadatas One metadata array per stream name, same index order is required
     * @return CompositeIterator
     */
    public function replay(array $streamNames, DateTimeInterface $since = null, array $metadatas)
    {
        if (empty($streamNames)) {
            throw new \InvalidArgumentException('No stream names given');
        }

        if (count($streamNames) !== count($metadatas)) {
            throw new \InvalidArgumentException(sprintf(
                'One metadata per stream name needed, given %s stream names but %s metadatas',
                count($streamNames),
                count($metadatas)
            ));
        }

        $iterators = [];
        foreach ($streamNames as $key => $streamName) {
            $iterators[] = $this->adapter->replay($streamName, $since, $metadatas[$key]);
        }

        return new CompositeIterator($iterators, function (Message $message1 = null, Message $message2) {
            if (null === $message1) {
                return true;
            }
            return (float) $message1->createdAt()->format('U.u') > (float) $message2->createdAt()->format('U.u');
        });
    }

    /**
     * Begin transaction
     *
     * @triggers beginTransaction
     */
    public function beginTransaction()
    {
        if (!$this->inTransaction && $this->adapter instanceof CanHandleTransaction) {
            $this->adapter->beginTransaction();
        }

        $this->inTransaction = true;

        $event = $this->actionEventEmitter->getNewActionEvent(
            __FUNCTION__,
            $this,
            ['inTransaction' => true]
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
        if (!$this->inTransaction) {
            throw new RuntimeException('Cannot commit transaction. EventStore has no active transaction');
        }

        $event = $this->getActionEventEmitter()->getNewActionEvent(__FUNCTION__ . '.pre', $this);

        $event->setParam('inTransaction', true);

        $this->getActionEventEmitter()->dispatch($event);

        if ($event->propagationIsStopped()) {
            $this->rollback();
            return;
        }

        $this->inTransaction = false;

        if ($this->adapter instanceof CanHandleTransaction) {
            $this->adapter->commit();
        }

        $event = $this->getActionEventEmitter()->getNewActionEvent(__FUNCTION__ . '.post', $this, ['recordedEvents' => $this->recordedEvents]);

        $this->recordedEvents = new ArrayIterator();

        $this->getActionEventEmitter()->dispatch($event);
    }

    /**
     * Rollback transaction
     *
     * @triggers rollback
     */
    public function rollback()
    {
        if (!$this->inTransaction) {
            throw new RuntimeException('Cannot rollback transaction. EventStore has no active transaction');
        }

        if (!$this->adapter instanceof CanHandleTransaction) {
            throw new RuntimeException('Adapter cannot handle transaction and therefore cannot rollback');
        }

        $this->adapter->rollback();

        $this->inTransaction = false;

        $event = $this->actionEventEmitter->getNewActionEvent(__FUNCTION__, $this);

        $this->actionEventEmitter->dispatch($event);

        $this->recordedEvents = [];
    }

    /**
     * @return bool
     */
    public function isInTransaction()
    {
        return $this->inTransaction;
    }

    /**
     * @return ActionEventEmitter
     */
    public function getActionEventEmitter()
    {
        return $this->actionEventEmitter;
    }
}
