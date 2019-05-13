<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2019 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Projection;

use Closure;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\EventStoreDecorator;
use Prooph\EventStore\Exception;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\NonTransactionalInMemoryEventStore;
use Prooph\EventStore\StreamIterator\MergedStreamIterator;
use Prooph\EventStore\StreamName;

final class InMemoryEventStoreQuery implements Query
{
    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var EventStore
     */
    private $innerEventStore;

    /**
     * @var array
     */
    private $streamPositions = [];

    /**
     * @var array
     */
    private $state = [];

    /**
     * @var callable|null
     */
    private $initCallback;

    /**
     * @var Closure|null
     */
    private $handler;

    /**
     * @var array
     */
    private $handlers = [];

    /**
     * @var boolean
     */
    private $isStopped = false;

    /**
     * @var ?string
     */
    private $currentStreamName = null;

    /**
     * @var array|null
     */
    private $query;

    /**
     * @var bool
     */
    private $triggerPcntlSignalDispatch;

    /**
     * @var MetadataMatcher|null
     */
    private $metadataMatcher;

    public function __construct(EventStore $eventStore, bool $triggerPcntlSignalDispatch = false)
    {
        $this->eventStore = $eventStore;
        $this->triggerPcntlSignalDispatch = $triggerPcntlSignalDispatch;

        while ($eventStore instanceof EventStoreDecorator) {
            $eventStore = $eventStore->getInnerEventStore();
        }

        if (
        ! (
            $eventStore instanceof InMemoryEventStore
            || $eventStore instanceof NonTransactionalInMemoryEventStore
        )
        ) {
            throw new Exception\InvalidArgumentException('Unknown event store instance given');
        }

        $this->innerEventStore = $eventStore;
    }

    public function init(Closure $callback): Query
    {
        if (null !== $this->initCallback) {
            throw new Exception\RuntimeException('Query is already initialized');
        }

        $callback = Closure::bind($callback, $this->createHandlerContext($this->currentStreamName));

        $result = $callback();

        if (\is_array($result)) {
            $this->state = $result;
        }

        $this->initCallback = $callback;

        return $this;
    }

    public function fromStream(string $streamName, MetadataMatcher $metadataMatcher = null): Query
    {
        if (null !== $this->query) {
            throw new Exception\RuntimeException('From was already called');
        }

        $this->query['streams'][] = $streamName;
        $this->metadataMatcher = $metadataMatcher;

        return $this;
    }

    public function fromStreams(string ...$streamNames): Query
    {
        if (null !== $this->query) {
            throw new Exception\RuntimeException('From was already called');
        }

        foreach ($streamNames as $streamName) {
            $this->query['streams'][] = $streamName;
        }

        return $this;
    }

    public function fromCategory(string $name): Query
    {
        if (null !== $this->query) {
            throw new Exception\RuntimeException('From was already called');
        }

        $this->query['categories'][] = $name;

        return $this;
    }

    public function fromCategories(string ...$names): Query
    {
        if (null !== $this->query) {
            throw new Exception\RuntimeException('From was already called');
        }

        foreach ($names as $name) {
            $this->query['categories'][] = $name;
        }

        return $this;
    }

    public function fromAll(): Query
    {
        if (null !== $this->query) {
            throw new Exception\RuntimeException('From was already called');
        }

        $this->query['all'] = true;

        return $this;
    }

    public function when(array $handlers): Query
    {
        if (null !== $this->handler || ! empty($this->handlers)) {
            throw new Exception\RuntimeException('When was already called');
        }

        foreach ($handlers as $eventName => $handler) {
            if (! \is_string($eventName)) {
                throw new Exception\InvalidArgumentException('Invalid event name given, string expected');
            }

            if (! $handler instanceof Closure) {
                throw new Exception\InvalidArgumentException('Invalid handler given, Closure expected');
            }

            $this->handlers[$eventName] = Closure::bind($handler, $this->createHandlerContext($this->currentStreamName));
        }

        return $this;
    }

    public function whenAny(Closure $handler): Query
    {
        if (null !== $this->handler || ! empty($this->handlers)) {
            throw new Exception\RuntimeException('When was already called');
        }

        $this->handler = Closure::bind($handler, $this->createHandlerContext($this->currentStreamName));

        return $this;
    }

    public function reset(): void
    {
        $this->streamPositions = [];

        $callback = $this->initCallback;

        if (\is_callable($callback)) {
            $result = $callback();

            if (\is_array($result)) {
                $this->state = $result;

                return;
            }
        }

        $this->state = [];
    }

    public function run(): void
    {
        if (null === $this->query
            || (null === $this->handler && empty($this->handlers))
        ) {
            throw new Exception\RuntimeException('No handlers configured');
        }

        $this->prepareStreamPositions();
        $singleHandler = null !== $this->handler;

        $eventStreams = [];

        foreach ($this->streamPositions as $streamName => $position) {
            try {
                $eventStreams[$streamName] = $this->eventStore->load(new StreamName($streamName), $position + 1, null, $this->metadataMatcher);
            } catch (Exception\StreamNotFound $e) {
                // ignore
                continue;
            }
        }

        $streamEvents = new MergedStreamIterator(\array_keys($eventStreams), ...\array_values($eventStreams));

        if ($singleHandler) {
            $this->handleStreamWithSingleHandler($streamEvents);
        } else {
            $this->handleStreamWithHandlers($streamEvents);
        }
    }

    public function stop(): void
    {
        $this->isStopped = true;
    }

    public function getState(): array
    {
        return $this->state;
    }

    private function handleStreamWithSingleHandler(MergedStreamIterator $events): void
    {
        $handler = $this->handler;

        /* @var Message $event */
        foreach ($events as $event) {
            if ($this->triggerPcntlSignalDispatch) {
                \pcntl_signal_dispatch();
            }

            $this->currentStreamName = $events->streamName();
            $this->streamPositions[$this->currentStreamName]++;

            $result = $handler($this->state, $event);

            if (\is_array($result)) {
                $this->state = $result;
            }

            if ($this->isStopped) {
                break;
            }
        }
    }

    private function handleStreamWithHandlers(MergedStreamIterator $events): void
    {
        /* @var Message $event */
        foreach ($events as $event) {
            if ($this->triggerPcntlSignalDispatch) {
                \pcntl_signal_dispatch();
            }

            $this->currentStreamName = $events->streamName();
            $this->streamPositions[$this->currentStreamName]++;

            if (! isset($this->handlers[$event->messageName()])) {
                continue;
            }

            $handler = $this->handlers[$event->messageName()];
            $result = $handler($this->state, $event);

            if (\is_array($result)) {
                $this->state = $result;
            }

            if ($this->isStopped) {
                break;
            }
        }
    }

    private function createHandlerContext(?string &$streamName)
    {
        return new class($this, $streamName) {
            /**
             * @var Query
             */
            private $query;

            /**
             * @var ?string
             */
            private $streamName;

            public function __construct(Query $query, ?string &$streamName)
            {
                $this->query = $query;
                $this->streamName = &$streamName;
            }

            public function stop(): void
            {
                $this->query->stop();
            }

            public function streamName(): ?string
            {
                return $this->streamName;
            }
        };
    }

    private function prepareStreamPositions(): void
    {
        $reflectionProperty = new \ReflectionProperty(\get_class($this->innerEventStore), 'streams');
        $reflectionProperty->setAccessible(true);

        $streamPositions = [];
        $streams = \array_keys($reflectionProperty->getValue($this->eventStore));

        if (isset($this->query['all'])) {
            foreach ($streams as $stream) {
                if (\substr($stream, 0, 1) === '$') {
                    // ignore internal streams
                    continue;
                }
                $streamPositions[$stream] = 0;
            }

            $this->streamPositions = \array_merge($streamPositions, $this->streamPositions);

            return;
        }

        if (isset($this->query['categories'])) {
            foreach ($streams as $stream) {
                foreach ($this->query['categories'] as $category) {
                    if (\substr($stream, 0, \strlen($category) + 1) === $category . '-') {
                        $streamPositions[$stream] = 0;
                        break;
                    }
                }
            }

            $this->streamPositions = \array_merge($streamPositions, $this->streamPositions);

            return;
        }

        // stream names given
        foreach ($this->query['streams'] as $stream) {
            $streamPositions[$stream] = 0;
        }

        $this->streamPositions = \array_merge($streamPositions, $this->streamPositions);
    }
}
