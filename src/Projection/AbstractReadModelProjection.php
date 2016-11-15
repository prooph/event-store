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

    public function run(): void
    {
        $this->load();

        if (null === $this->position
            || (null === $this->handler && empty($this->handlers))
        ) {
            throw new RuntimeException('No handlers configured');
        }

        if (! $this->readModelProjection->projectionIsInitialized()) {
            $this->readModelProjection->initProjection();
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

    protected function resetProjection(): void
    {
        $this->readModelProjection->resetProjection();
    }
}
