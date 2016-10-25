<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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

    public function __construct(Adapter $adapter, ActionEventEmitter $actionEventEmitter)
    {
        $this->adapter = $adapter;
        $this->actionEventEmitter = $actionEventEmitter;
        $this->recordedEvents = new ArrayIterator();
    }

    public function getAdapter(): Adapter
    {
        return $this->adapter;
    }

    public function getRecordedEvents(): Iterator
    {
        return $this->recordedEvents;
    }

    /**
     * @throws Exception\RuntimeException
     */
    public function create(Stream $stream): void
    {
        $argv = ['stream' => $stream];

        $event = $this->actionEventEmitter->getNewActionEvent(__FUNCTION__ . '.pre', $this, $argv);

        $this->actionEventEmitter->dispatch($event);

        if ($event->propagationIsStopped()) {
            return;
        }

        if (! $this->inTransaction) {
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
     * @throws Exception\RuntimeException
     */
    public function appendTo(StreamName $streamName, Iterator $streamEvents): void
    {
        $argv = ['streamName' => $streamName, 'streamEvents' => $streamEvents];

        $event = $this->actionEventEmitter->getNewActionEvent(__FUNCTION__ . '.pre', $this, $argv);

        $this->getActionEventEmitter()->dispatch($event);

        if ($event->propagationIsStopped()) {
            return;
        }

        if (! $this->inTransaction) {
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
     * @throws Exception\StreamNotFoundException
     */
    public function load(
        StreamName $streamName,
        int $fromNumber = 0,
        int $toNumber = null,
        bool $forward = true
    ): Stream {
        $argv = [
            'streamName' => $streamName,
            'fromNumber' => $fromNumber,
            'toNumber'   => $toNumber,
            'forward'    => $forward,
        ];

        $event = $this->actionEventEmitter->getNewActionEvent(__FUNCTION__ . '.pre', $this, $argv);

        $this->getActionEventEmitter()->dispatch($event);

        if ($event->propagationIsStopped()) {
            $stream = $event->getParam('stream', false);

            if ($stream instanceof Stream && $stream->streamName()->toString() == $streamName->toString()) {
                return $stream;
            }

            throw StreamNotFoundException::with($streamName);
        }

        $streamName = $event->getParam('streamName');
        $fromNumber = $event->getParam('fromNumber');
        $toNumber   = $event->getParam('toNumber');
        $forward    = $event->getParam('forward');

        $stream = $this->adapter->load($streamName, $fromNumber, $toNumber, $forward);

        if (! $stream) {
            throw StreamNotFoundException::with($streamName);
        }

        $event->setName(__FUNCTION__ . '.post');

        $event->setParam('stream', $stream);

        $this->getActionEventEmitter()->dispatch($event);

        if ($event->propagationIsStopped()) {
            throw StreamNotFoundException::with($streamName);
        }

        return $event->getParam('stream');
    }

    public function loadEventsByMetadataFrom(
        StreamName $streamName,
        array $metadata,
        int $fromNumber = 0,
        int $toNumber = null,
        bool $forward = true
    ): Iterator {
        $argv = [
            'streamName' => $streamName,
            'fromNumber' => $fromNumber,
            'toNumber'   => $toNumber,
            'forward'    => $forward,
        ];

        $event = $this->actionEventEmitter->getNewActionEvent(__FUNCTION__ . '.pre', $this, $argv);

        $this->getActionEventEmitter()->dispatch($event);

        if ($event->propagationIsStopped()) {
            return $event->getParam('streamEvents', new ArrayIterator([]));
        }

        $streamName = $event->getParam('streamName');
        $fromNumber = $event->getParam('fromNumber');
        $toNumber   = $event->getParam('toNumber');
        $forward    = $event->getParam('forward');

        $events = $this->adapter->loadEvents($streamName, $metadata, $fromNumber, $toNumber, $forward);

        $event->setName(__FUNCTION__ . '.post');

        $event->setParam('streamEvents', $events);

        $this->getActionEventEmitter()->dispatch($event);

        if ($event->propagationIsStopped()) {
            return new ArrayIterator([]);
        }

        return $event->getParam('streamEvents');
    }

    /**
     * @param callable $callable
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    public function transactional(callable $callable)
    {
        $this->beginTransaction();

        try {
            $result = $callable($this);
            $this->commit();
        } catch (\Throwable $e) {
            $this->rollback();

            throw $e;
        }

        return $result ?: true;
    }

    /**
     * Begin transaction
     *
     * @triggers beginTransaction
     */
    public function beginTransaction(): void
    {
        if (! $this->inTransaction && $this->adapter instanceof CanHandleTransaction) {
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
    public function commit(): void
    {
        if (! $this->inTransaction) {
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
    public function rollback(): void
    {
        if (! $this->inTransaction) {
            throw new RuntimeException('Cannot rollback transaction. EventStore has no active transaction');
        }

        if (! $this->adapter instanceof CanHandleTransaction) {
            throw new RuntimeException('Adapter cannot handle transaction and therefore cannot rollback');
        }

        $this->adapter->rollback();

        $this->inTransaction = false;

        $event = $this->actionEventEmitter->getNewActionEvent(__FUNCTION__, $this);

        $this->actionEventEmitter->dispatch($event);

        $this->recordedEvents = new ArrayIterator();
    }

    public function isInTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function getActionEventEmitter(): ActionEventEmitter
    {
        return $this->actionEventEmitter;
    }
}
