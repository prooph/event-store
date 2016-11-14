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

namespace Prooph\EventStore\StreamProjection;

use ArrayIterator;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use stdClass;

abstract class AbstractStreamProjection extends AbstractQuery implements StreamProjection
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $persistBatches;

    /**
     * @var bool
     */
    protected $emitEnabled;

    public function __construct(EventStore $eventStore, string $name, int $persistBatches, bool $enableEmit)
    {
        parent::__construct($eventStore);

        $this->name = $name;
        $this->persistBatches = $persistBatches;
        $this->emitEnabled = $enableEmit;

        if ($enableEmit && ! $this->eventStore->hasStream(new StreamName($name))) {
            $this->eventStore->create(new Stream(new StreamName("$name"), new ArrayIterator()));
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function emit(Message $event): void
    {
        if (! $this->emitEnabled) {
            throw new RuntimeException('Emit is disabled');
        }

        $this->eventStore->appendTo(new StreamName("$this->name"), new ArrayIterator([$event]));
    }

    public function linkTo(string $streamName, Message $event): void
    {
        $this->eventStore->appendTo(new StreamName($streamName), new ArrayIterator([$event]));
    }

    abstract protected function load();

    abstract protected function persist();

    public function run(): void
    {
        $this->load();

        if (null === $this->position
            || (null === $this->handler && empty($this->handlers))
        ) {
            throw new RuntimeException('No projection configured');
        }

        $singleHandler = null !== $this->handler;

        $count = 0;
        $persisted = false;

        foreach ($this->position->streamPositions() as $streamName => $position) {
            $stream = $this->eventStore->load(new StreamName($streamName), $position + 1);

            foreach ($stream->streamEvents() as $event) {
                /* @var Message $event */
                $this->position->inc($streamName);
                $count++;
                if ($singleHandler) {
                    $handler = $this->handler;
                    $result = $handler($this->state, $event);
                    if ($result instanceof stdClass) {
                        $this->state = $result;
                    }
                } else {
                    foreach ($this->handlers as $eventName => $handler) {
                        if ($eventName === $event->messageName()) {
                            $result = $handler($this->state, $event);
                            if ($result instanceof stdClass) {
                                $this->state = $result;
                            }
                            break;
                        }
                    }
                }
                if ($count % $this->persistBatches === 0) {
                    $this->persist();
                    $persisted = true;
                } else {
                    $persisted = false;
                }
            }
        }

        if (! $persisted) {
            $this->persist();
        }
    }
}
