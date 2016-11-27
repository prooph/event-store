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

namespace Prooph\EventStore\Projection;

use Closure;
use Iterator;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\StreamName;

abstract class AbstractQuery implements Query
{
    /**
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @var Position
     */
    protected $position;

    /**
     * @var array
     */
    protected $state = [];

    /**
     * @var callable|null
     */
    protected $initCallback;

    /**
     * @var Closure|null
     */
    protected $handler;

    /**
     * @var array
     */
    protected $handlers = [];

    /**
     * @var boolean
     */
    protected $isStopped = false;

    public function __construct(EventStore $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    public function init(Closure $callback): Query
    {
        if (null !== $this->initCallback) {
            throw new RuntimeException('Projection already initialized');
        }

        $callback = Closure::bind($callback, $this->createHandlerContext(null));

        $result = $callback();

        if (is_array($result)) {
            $this->state = $result;
        }

        $this->initCallback = $callback;

        return $this;
    }

    public function fromStream(string $streamName): Query
    {
        if (null !== $this->position) {
            throw new RuntimeException('From was already called');
        }

        $this->position = new Position([$streamName => 0]);

        return $this;
    }

    public function fromStreams(string ...$streamNames): Query
    {
        if (null !== $this->position) {
            throw new RuntimeException('From was already called');
        }

        $streamPositions = [];

        foreach ($streamNames as $streamName) {
            $streamPositions[$streamName] = 0;
        }

        $this->position = new Position($streamPositions);

        return $this;
    }

    public function when(array $handlers): Query
    {
        if (null !== $this->handler || ! empty($this->handlers)) {
            throw new RuntimeException('When was already called');
        }

        foreach ($handlers as $eventName => $handler) {
            if (! is_string($eventName)) {
                throw new InvalidArgumentException('Invalid event name given, string expected');
            }

            if (! $handler instanceof Closure) {
                throw new InvalidArgumentException('Invalid handler given, Closure expected');
            }

            $this->handlers[$eventName] = $handler;
        }

        return $this;
    }

    public function whenAny(Closure $handler): Query
    {
        if (null !== $this->handler || ! empty($this->handlers)) {
            throw new RuntimeException('When was already called');
        }

        $this->handler = $handler;

        return $this;
    }

    public function reset(): void
    {
        if (null !== $this->position) {
            $this->position->reset();
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
    }

    public function run(): void
    {
        if (null === $this->position
            || (null === $this->handler && empty($this->handlers))
        ) {
            throw new RuntimeException('No handlers configured');
        }

        $singleHandler = null !== $this->handler;

        foreach ($this->position->streamPositions() as $streamName => $position) {
            $stream = $this->eventStore->load(new StreamName($streamName), $position + 1);

            if ($singleHandler) {
                $this->handleStreamWithSingleHandler($streamName, $stream->streamEvents());
            } else {
                $this->handleStreamWithHandlers($streamName, $stream->streamEvents());
            }

            if ($this->isStopped) {
                break;
            }
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

    protected function handleStreamWithSingleHandler(string $streamName, Iterator $events): void
    {
        $handler = $this->handler;
        $handler = Closure::bind($handler, $this->createHandlerContext($streamName));

        foreach ($events as $event) {
            /* @var Message $event */
            $this->position->inc($streamName);

            $result = $handler($this->state, $event);

            if (is_array($result)) {
                $this->state = $result;
            }

            if ($this->isStopped) {
                break;
            }
        }
    }

    protected function handleStreamWithHandlers(string $streamName, Iterator $events): void
    {
        foreach ($this->handlers as $messageName => $handler) {
            $this->handlers[$messageName] = Closure::bind($handler, $this->createHandlerContext($streamName));
        }

        foreach ($events as $event) {
            /* @var Message $event */
            $this->position->inc($streamName);

            if (! isset($this->handlers[$event->messageName()])) {
                continue;
            }

            $handler = $this->handlers[$event->messageName()];
            $result = $handler($this->state, $event);

            if (is_array($result)) {
                $this->state = $result;
            }

            if ($this->isStopped) {
                break;
            }
        }
    }

    protected function createHandlerContext(?string $streamName)
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

            public function __construct(Query $query, ?string $streamName)
            {
                $this->query = $query;
                $this->streamName = $streamName;
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
}
