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

use ArrayIterator;
use Closure;
use Iterator;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;

abstract class AbstractProjection extends AbstractQuery implements Projection
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $emitEnabled;

    public function __construct(EventStore $eventStore, string $name, bool $emitEnabled)
    {
        parent::__construct($eventStore);

        $this->name = $name;
        $this->emitEnabled = $emitEnabled;
    }

    abstract protected function load(): void;

    abstract protected function persist(): void;

    protected function resetProjection(): void
    {
        $this->eventStore->delete(new StreamName($this->name));
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
        $this->eventStore->appendTo(new StreamName($streamName), new ArrayIterator([$event]));
    }

    public function reset(): void
    {
        parent::reset();

        $this->resetProjection();
    }

    public function run(): void
    {
        if (null === $this->position
            || (null === $this->handler && empty($this->handlers))
        ) {
            throw new RuntimeException('No handlers configured');
        }

        $this->load();

        if ($this->emitEnabled && ! $this->eventStore->hasStream(new StreamName($this->name))) {
            $this->eventStore->create(new Stream(new StreamName($this->name), new ArrayIterator()));
        }

        $singleHandler = null !== $this->handler;

        foreach ($this->position->streamPositions() as $streamName => $position) {
            try {
                $stream = $this->eventStore->load(new StreamName($streamName), $position + 1);
            } catch (StreamNotFound $e) {
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
    }

    protected function createHandlerContext(?string $streamName)
    {
        if ($this->emitEnabled) {
            return new class($this, $streamName) {
                /**
                 * @var Projection
                 */
                private $projection;

                /**
                 * @var ?string
                 */
                private $streamName;

                public function __construct(Projection $projection, ?string $streamName)
                {
                    $this->projection = $projection;
                    $this->streamName = $streamName;
                }

                public function stop(): void
                {
                    $this->projection->stop();
                }

                public function linkTo(string $streamName, Message $event): void
                {
                    $this->projection->linkTo($streamName, $event);
                }

                public function emit(Message $event): void
                {
                    $this->projection->emit($event);
                }

                public function streamName(): ?string
                {
                    return $this->streamName;
                }
            };
        }

        return new class($this, $streamName) {
            /**
             * @var Projection
             */
            private $projection;

            /**
             * @var ?string
             */
            private $streamName;

            public function __construct(Projection $projection, ?string $streamName)
            {
                $this->projection = $projection;
                $this->streamName = $streamName;
            }

            public function stop(): void
            {
                $this->projection->stop();
            }

            public function linkTo(string $streamName, Message $event): void
            {
                $this->projection->linkTo($streamName, $event);
            }

            public function streamName(): ?string
            {
                return $this->streamName;
            }
        };
    }
}
