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
use stdClass;

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
     * @var stdClass
     */
    protected $state;

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

    public function __construct(EventStore $eventStore)
    {
        $this->eventStore = $eventStore;
        $this->state = new stdClass();
    }

    public function init(callable $callback): Query
    {
        if (null !== $this->initCallback) {
            throw new RuntimeException('Projection already initialized');
        }

        $this->state = $callback();

        if (! $this->state instanceof stdClass) {
            throw new RuntimeException('Init callback is expected to return an instance of stdClass');
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

            $handler = Closure::bind($handler, $this);

            $this->handlers[$eventName] = $handler;
        }

        return $this;
    }

    public function whenAny(Closure $closure): Query
    {
        if (null !== $this->handler || ! empty($this->handlers)) {
            throw new RuntimeException('When was already called');
        }

        $closure = Closure::bind($closure, $this);

        $this->handler = $closure;

        return $this;
    }

    public function reset(): void
    {
        if (null !== $this->position) {
            $this->position->reset();
        }

        $callback = $this->initCallback;

        if (is_callable($callback)) {
            $this->state = $callback();
        } else {
            $this->state = new stdClass();
        }
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
        }
    }

    public function getState(): stdClass
    {
        return $this->state;
    }

    private function handleStreamWithSingleHandler(string $streamName, Iterator $events): void
    {
        foreach ($events as $event) {
            /* @var Message $event */
            $this->position->inc($streamName);
            $handler = $this->handler;
            $handler($this->state, $event);
        }
    }

    private function handleStreamWithHandlers(string $streamName, Iterator $events): void
    {
        foreach ($events as $event) {
            /* @var Message $event */
            $this->position->inc($streamName);
            if (! isset($this->handlers[$event->messageName()])) {
                continue;
            }
            $handler = $this->handlers[$event->messageName()];
            $handler($this->state, $event);
        }
    }
}