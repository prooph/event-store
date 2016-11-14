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
use Iterator;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\RuntimeException;
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

    abstract protected function resetProjection(): void;

    public function getName(): string
    {
        return $this->name;
    }

    public function emit(Message $event): void
    {
        if (! $this->emitEnabled) {
            throw new RuntimeException('Emit is disabled');
        }

        $this->eventStore->appendTo(new StreamName($this->name), new ArrayIterator([$event]));
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
        $this->load();

        if (null === $this->position
            || (null === $this->handler && empty($this->handlers))
        ) {
            throw new RuntimeException('No handlers configured');
        }

        if ($this->emitEnabled && ! $this->eventStore->hasStream(new StreamName($this->name))) {
            $this->eventStore->create(new Stream(new StreamName($this->name), new ArrayIterator()));
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

    private function handleStreamWithSingleHandler(string $streamName, Iterator $events): void
    {
        foreach ($events as $event) {
            /* @var Message $event */
            $this->position->inc($streamName);
            $handler = $this->handler;
            $handler($this->state, $event);
            $this->persist();
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
            $this->persist();
        }
    }
}
