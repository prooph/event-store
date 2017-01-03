<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
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
use Prooph\EventStore\Exception;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Prooph\EventStore\Util\ArrayCache;

final class InMemoryEventStoreReadModelProjection implements ReadModelProjection
{
    /**
     * @var array
     */
    private $knownStreams;

    /**
     * @var ReadModel
     */
    private $readModel;

    /**
     * @var string
     */
    private $name;

    /**
     * @var ArrayCache
     */
    private $cachedStreamNames;

    /**
     * @var int
     */
    private $eventCounter = 0;

    /**
     * @var int
     */
    private $persistBlockSize;

    /**
     * @var InMemoryEventStore
     */
    private $eventStore;

    /**
     * @var array
     */
    private $streamPositions;

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
     * @var int
     */
    private $sleep;

    public function __construct(
        InMemoryEventStore $eventStore,
        string $name,
        ReadModel $readModel,
        int $cacheSize,
        int $persistBlockSize,
        int $sleep
    ) {
        if ($persistBlockSize <= 0) {
            throw new Exception\InvalidArgumentException('PersistBlockSize must be a positive integer');
        }

        $this->eventStore = $eventStore;
        $this->name = $name;
        $this->cachedStreamNames = new ArrayCache($cacheSize);
        $this->persistBlockSize = $persistBlockSize;
        $this->readModel = $readModel;
        $this->sleep = $sleep;

        $reflectionProperty = new \ReflectionProperty(get_class($this->eventStore), 'streams');
        $reflectionProperty->setAccessible(true);

        $this->knownStreams = array_keys($reflectionProperty->getValue($this->eventStore));
    }

    public function init(Closure $callback): Query
    {
        if (null !== $this->initCallback) {
            throw new Exception\RuntimeException('Projection already initialized');
        }

        $callback = Closure::bind($callback, $this->createHandlerContext($this->currentStreamName));

        $result = $callback();

        if (is_array($result)) {
            $this->state = $result;
        }

        $this->initCallback = $callback;

        return $this;
    }

    public function fromStream(string $streamName): Query
    {
        if (null !== $this->streamPositions) {
            throw new Exception\RuntimeException('From was already called');
        }

        $this->streamPositions = [$streamName => 0];

        return $this;
    }

    public function fromStreams(string ...$streamNames): Query
    {
        if (null !== $this->streamPositions) {
            throw new Exception\RuntimeException('From was already called');
        }

        foreach ($streamNames as $streamName) {
            $this->streamPositions[$streamName] = 0;
        }

        return $this;
    }

    public function fromCategory(string $name): Query
    {
        if (null !== $this->streamPositions) {
            throw new Exception\RuntimeException('From was already called');
        }

        $this->streamPositions = [];

        foreach ($this->knownStreams as $stream) {
            if (substr($stream, 0, strlen($name) + 1) === $name . '-') {
                $this->streamPositions[$stream] = 0;
            }
        }

        return $this;
    }

    public function fromCategories(string ...$names): Query
    {
        if (null !== $this->streamPositions) {
            throw new Exception\RuntimeException('From was already called');
        }

        $this->streamPositions = [];

        foreach ($this->knownStreams as $stream) {
            foreach ($names as $name) {
                if (substr($stream, 0, strlen($name) + 1) === $name . '-') {
                    $this->streamPositions[$stream] = 0;
                    break;
                }
            }
        }

        return $this;
    }

    public function fromAll(): Query
    {
        if (null !== $this->streamPositions) {
            throw new Exception\RuntimeException('From was already called');
        }

        $this->streamPositions = [];

        foreach ($this->knownStreams as $stream) {
            if (substr($stream, 0, 1) === '$') {
                // ignore internal streams
                continue;
            }
            $this->streamPositions[$stream] = 0;
        }

        return $this;
    }

    public function when(array $handlers): Query
    {
        if (null !== $this->handler || ! empty($this->handlers)) {
            throw new Exception\RuntimeException('When was already called');
        }

        foreach ($handlers as $eventName => $handler) {
            if (! is_string($eventName)) {
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

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        if ($deleteEmittedEvents) {
            $this->readModel->delete();
        }
    }

    public function run(bool $keepRunning = true): void
    {
        if (null === $this->streamPositions
            || (null === $this->handler && empty($this->handlers))
        ) {
            throw new Exception\RuntimeException('No handlers configured');
        }

        if (! $this->readModel->isInitialized()) {
            $this->readModel->init();
        }

        do {
            $singleHandler = null !== $this->handler;

            $eventCounter = 0;

            foreach ($this->streamPositions as $streamName => $position) {
                try {
                    $stream = $this->eventStore->load(new StreamName($streamName), $position + 1);
                } catch (Exception\StreamNotFound $e) {
                    // no newer events found
                    continue;
                }

                if ($singleHandler) {
                    $this->handleStreamWithSingleHandler($streamName, $stream->streamEvents());
                } else {
                    $this->handleStreamWithHandlers($streamName, $stream->streamEvents());
                }

                if ($this->isStopped) {
                    break;
                }
            }

            $this->readModel()->persist();

            if (0 === $eventCounter) {
                usleep($this->sleep);
            }
        } while ($keepRunning && ! $this->isStopped);
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
        if (null !== $this->streamPositions) {
            $this->streamPositions = array_map(
                function (): int {
                    return 0;
                },
                $this->streamPositions
            );
        }

        $callback = $this->initCallback;

        if (is_callable($callback)) {
            $result = $callback();

            if (is_array($result)) {
                $this->state = $result;

                return;
            }
        }

        $this->state = [];

        $this->readModel->delete();
    }

    private function handleStreamWithSingleHandler(string $streamName, Iterator $events): void
    {
        $this->currentStreamName = $streamName;
        $handler = $this->handler;

        foreach ($events as $event) {
            /* @var Message $event */
            $this->streamPositions[$streamName]++;
            $this->eventCounter++;

            $result = $handler($this->state, $event);

            if (is_array($result)) {
                $this->state = $result;
            }

            if ($this->eventCounter === $this->persistBlockSize) {
                $this->readModel()->persist();
                $this->eventCounter = 0;
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
            /* @var Message $event */
            $this->streamPositions[$streamName]++;
            $this->eventCounter++;

            if (! isset($this->handlers[$event->messageName()])) {
                continue;
            }

            $handler = $this->handlers[$event->messageName()];
            $result = $handler($this->state, $event);

            if (is_array($result)) {
                $this->state = $result;
            }

            if ($this->eventCounter === $this->persistBlockSize) {
                $this->readModel()->persist();
                $this->eventCounter = 0;
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
             * @var ReadModelProjection
             */
            private $projection;

            /**
             * @var ?string
             */
            private $streamName;

            public function __construct(ReadModelProjection $projection, ?string &$streamName)
            {
                $this->projection = $projection;
                $this->streamName = &$streamName;
            }

            public function stop(): void
            {
                $this->projection->stop();
            }

            public function readModel(): ReadModel
            {
                return $this->projection->readModel();
            }

            public function streamName(): ?string
            {
                return $this->streamName;
            }
        };
    }
}
