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

abstract class AbstractReadModelProjection extends AbstractProjection implements ReadModelProjection
{
    /**
     * @var ReadModel
     */
    protected $readModel;

    public function __construct(
        EventStore $eventStore,
        string $name,
        ReadModel $readModel,
        int $cacheSize,
        int $persistBlockSize
    ) {
        parent::__construct($eventStore, $name, $cacheSize, $persistBlockSize);

        $this->readModel = $readModel;
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
            throw new RuntimeException('No handlers configured');
        }

        if (! $this->readModel->isInitialized()) {
            $this->readModel->init();
        }

        do {
            $this->load();

            $singleHandler = null !== $this->handler;

            foreach ($this->streamPositions as $streamName => $position) {
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

            $this->persist();
        } while ($keepRunning && ! $this->isStopped);
    }

    protected function resetProjection(): void
    {
        $this->readModel->reset();
    }

    protected function createHandlerContext(?string &$streamName)
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
