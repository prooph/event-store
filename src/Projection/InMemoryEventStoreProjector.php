<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Projection;

use ArrayIterator;
use Closure;
use Iterator;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\EventStoreDecorator;
use Prooph\EventStore\Exception;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\NonTransactionalInMemoryEventStore;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Prooph\EventStore\Util\ArrayCache;

final class InMemoryEventStoreProjector implements Projector
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var ProjectionStatus
     */
    private $status;

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
     * @var ArrayCache
     */
    private $cachedStreamNames;

    /**
     * @var boolean
     */
    private $isStopped = false;

    /**
     * @var ?string
     */
    private $currentStreamName = null;

    /**
     * @var int
     */
    private $sleep;

    /**
     * @var bool
     */
    private $triggerPcntlSignalDispatch;

    /**
     * @var array|null
     */
    private $query;

    /**
     * @var bool
     */
    private $streamCreated = false;

    /**
     * @var MetadataMatcher|null
     */
    private $metadataMatcher;

    public function __construct(
        EventStore $eventStore,
        string $name,
        int $cacheSize,
        int $sleep,
        bool $triggerPcntlSignalDispatch = false
    ) {
        if ($cacheSize < 1) {
            throw new Exception\InvalidArgumentException('cache size must be a positive integer');
        }

        if ($sleep < 1) {
            throw new Exception\InvalidArgumentException('sleep must be a positive integer');
        }

        if ($triggerPcntlSignalDispatch && ! \extension_loaded('pcntl')) {
            throw Exception\ExtensionNotLoadedException::withName('pcntl');
        }

        $this->eventStore = $eventStore;
        $this->name = $name;
        $this->cachedStreamNames = new ArrayCache($cacheSize);
        $this->sleep = $sleep;
        $this->status = ProjectionStatus::IDLE();
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

    public function init(Closure $callback): Projector
    {
        if (null !== $this->initCallback) {
            throw new Exception\RuntimeException('Projection already initialized');
        }

        $callback = Closure::bind($callback, $this->createHandlerContext($this->currentStreamName));

        $result = $callback();

        if (\is_array($result)) {
            $this->state = $result;
        }

        $this->initCallback = $callback;

        return $this;
    }

    public function fromStream(string $streamName, MetadataMatcher $metadataMatcher = null): Projector
    {
        if (null !== $this->query) {
            throw new Exception\RuntimeException('From was already called');
        }

        $this->query['streams'][] = $streamName;
        $this->metadataMatcher = $metadataMatcher;

        return $this;
    }

    public function fromStreams(string ...$streamNames): Projector
    {
        if (null !== $this->query) {
            throw new Exception\RuntimeException('From was already called');
        }

        foreach ($streamNames as $streamName) {
            $this->query['streams'][] = $streamName;
        }

        return $this;
    }

    public function fromCategory(string $name): Projector
    {
        if (null !== $this->query) {
            throw new Exception\RuntimeException('From was already called');
        }

        $this->query['categories'][] = $name;

        return $this;
    }

    public function fromCategories(string ...$names): Projector
    {
        if (null !== $this->query) {
            throw new Exception\RuntimeException('From was already called');
        }

        foreach ($names as $name) {
            $this->query['categories'][] = $name;
        }

        return $this;
    }

    public function fromAll(): Projector
    {
        if (null !== $this->query) {
            throw new Exception\RuntimeException('From was already called');
        }

        $this->query['all'] = true;

        return $this;
    }

    public function when(array $handlers): Projector
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

    public function whenAny(Closure $handler): Projector
    {
        if (null !== $this->handler || ! empty($this->handlers)) {
            throw new Exception\RuntimeException('When was already called');
        }

        $this->handler = Closure::bind($handler, $this->createHandlerContext($this->currentStreamName));

        return $this;
    }

    public function stop(): void
    {
        $this->isStopped = true;
    }

    public function getState(): array
    {
        return $this->state;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function emit(Message $event): void
    {
        if (! $this->streamCreated || ! $this->eventStore->hasStream(new StreamName($this->name))) {
            $this->eventStore->create(new Stream(new StreamName($this->name), new ArrayIterator()));
            $this->streamCreated = true;
        }

        $this->linkTo($this->name, $event);
    }

    public function linkTo(string $streamName, Message $event): void
    {
        $sn = new StreamName($streamName);

        if ($this->cachedStreamNames->has($streamName)) {
            $append = true;
        } else {
            $this->cachedStreamNames->rollingAppend($streamName);
            $append = $this->eventStore->hasStream($sn);
        }

        if ($append) {
            $this->eventStore->appendTo($sn, new ArrayIterator([$event]));
        } else {
            $this->eventStore->create(new Stream($sn, new ArrayIterator([$event])));
        }
    }

    public function reset(): void
    {
        $this->streamPositions = [];

        $callback = $this->initCallback;

        try {
            $this->eventStore->delete(new StreamName($this->name));
        } catch (Exception\StreamNotFound $exception) {
            // ignore
        }

        if (\is_callable($callback)) {
            $result = $callback();

            if (\is_array($result)) {
                $this->state = $result;

                return;
            }
        }

        $this->state = [];
    }

    public function run(bool $keepRunning = true): void
    {
        if (null === $this->query
            || (null === $this->handler && empty($this->handlers))
        ) {
            throw new Exception\RuntimeException('No handlers configured');
        }

        $this->prepareStreamPositions();
        $this->isStopped = false;
        $this->status = ProjectionStatus::RUNNING();

        do {
            $singleHandler = null !== $this->handler;

            $eventCounter = 0;

            foreach ($this->streamPositions as $streamName => $position) {
                try {
                    $streamEvents = $this->eventStore->load(new StreamName($streamName), $position + 1, null, $this->metadataMatcher);
                } catch (Exception\StreamNotFound $e) {
                    // ignore
                    continue;
                }

                if ($singleHandler) {
                    $this->handleStreamWithSingleHandler($streamName, $streamEvents);
                } else {
                    $this->handleStreamWithHandlers($streamName, $streamEvents);
                }

                if ($this->isStopped) {
                    break;
                }
            }

            if (0 === $eventCounter) {
                \usleep($this->sleep);
            }

            if ($this->triggerPcntlSignalDispatch) {
                \pcntl_signal_dispatch();
            }
        } while ($keepRunning && ! $this->isStopped);

        $this->status = ProjectionStatus::IDLE();
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        if ($deleteEmittedEvents) {
            try {
                $this->eventStore->delete(new StreamName($this->name));
            } catch (Exception\StreamNotFound $e) {
                // ignore
            }
        }

        $this->streamPositions = [];
    }

    private function handleStreamWithSingleHandler(string $streamName, Iterator $events): void
    {
        $this->currentStreamName = $streamName;
        $handler = $this->handler;

        foreach ($events as $event) {
            if ($this->triggerPcntlSignalDispatch) {
                \pcntl_signal_dispatch();
            }
            /* @var Message $event */
            $this->streamPositions[$streamName]++;

            $result = $handler($this->state, $event);

            if (\is_array($result)) {
                $this->state = $result;
            }

            if ($this->isStopped) {
                break;
            }
        }
    }

    private function handleStreamWithHandlers(string $streamName, Iterator $events): void
    {
        $this->currentStreamName = $streamName;

        foreach ($events as $event) {
            if ($this->triggerPcntlSignalDispatch) {
                \pcntl_signal_dispatch();
            }
            /* @var Message $event */
            $this->streamPositions[$streamName]++;

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
             * @var Projector
             */
            private $projector;

            /**
             * @var ?string
             */
            private $streamName;

            public function __construct(Projector $projector, ?string &$streamName)
            {
                $this->projector = $projector;
                $this->streamName = &$streamName;
            }

            public function stop(): void
            {
                $this->projector->stop();
            }

            public function linkTo(string $streamName, Message $event): void
            {
                $this->projector->linkTo($streamName, $event);
            }

            public function emit(Message $event): void
            {
                $this->projector->emit($event);
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
