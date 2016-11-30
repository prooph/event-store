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

use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\StreamName;

abstract class AbstractReadModelProjection extends AbstractProjection
{
    /**
     * @var ReadModelProjection
     */
    private $readModelProjection;

    public function __construct(EventStore $eventStore, string $name, ReadModelProjection $readModelProjection)
    {
        parent::__construct($eventStore, $name, false);

        $this->readModelProjection = $readModelProjection;
    }

    public function readModelProjection(): ReadModelProjection
    {
        return $this->readModelProjection;
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        if ($deleteEmittedEvents) {
            $this->readModelProjection->deleteProjection();
        }
    }

    public function run(bool $keepRunning = true): void
    {
        if (null === $this->position
            || (null === $this->handler && empty($this->handlers))
        ) {
            throw new RuntimeException('No handlers configured');
        }

        do {
            $this->load();

            if (! $this->readModelProjection->projectionIsInitialized()) {
                $this->readModelProjection->initProjection();
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
        } while ($keepRunning && ! $this->isStopped);
    }

    protected function resetProjection(): void
    {
        $this->readModelProjection->resetProjection();
    }

    protected function createHandlerContext(?string $streamName)
    {
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

            public function readModelProjection(): ReadModelProjection
            {
                return $this->projection->readModelProjection();
            }

            public function streamName(): ?string
            {
                return $this->streamName;
            }
        };
    }
}
