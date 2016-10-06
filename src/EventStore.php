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
    public function load(StreamName $streamName, ?int $minVersion = null): Stream
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

    public function loadEventsByMetadataFrom(StreamName $streamName, array $metadata, ?int $minVersion = null): Iterator
    {
        $argv = ['streamName' => $streamName, 'metadata' => $metadata, 'minVersion' => $minVersion];

        $event = $this->actionEventEmitter->getNewActionEvent(__FUNCTION__ . '.pre', $this, $argv);

        $this->getActionEventEmitter()->dispatch($event);

        if ($event->propagationIsStopped()) {
            return $event->getParam('streamEvents', new ArrayIterator([]));
        }

        $streamName = $event->getParam('streamName');
        $metadata = $event->getParam('metadata');
        $minVersion = $event->getParam('minVersion');

        $events = $this->adapter->loadEvents($streamName, $metadata, $minVersion);

        $event->setName(__FUNCTION__ . '.post');

        $event->setParam('streamEvents', $events);

        $this->getActionEventEmitter()->dispatch($event);

        if ($event->propagationIsStopped()) {
            return new ArrayIterator([]);
        }

        return $event->getParam('streamEvents');
    }

    /**
     * @param StreamName[] $streamNames
     * @param DateTimeInterface|null $since
     * @param null|array $metadatas One metadata array per stream name, same index order is required
     * @return CompositeIterator
     * @throws Exception\InvalidArgumentException
     */
    public function replay(array $streamNames, DateTimeInterface $since = null, array $metadatas = null): CompositeIterator
    {
        if (empty($streamNames)) {
            throw new Exception\InvalidArgumentException('No stream names given');
        }

        if (null === $metadatas) {
            $metadatas = array_fill(0, count($streamNames), []);
        }

        if (count($streamNames) !== count($metadatas)) {
            throw new Exception\InvalidArgumentException(sprintf(
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
            return $message1->createdAt()->format('U.u') > $message2->createdAt()->format('U.u');
        });
    }

    /**
     * @param callable $callable
     * @throws \Exception
     * @return mixed
     */
    public function transactional(callable $callable)
    {
        $this->beginTransaction();

        try {
            $result = $callable($this);
            $this->commit();
        } catch (\Exception $e) {
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
