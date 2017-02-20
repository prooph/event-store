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

use Prooph\EventStore\EventStore;
use Prooph\EventStore\EventStoreDecorator;
use Prooph\EventStore\Exception;
use Prooph\EventStore\InMemoryEventStore;

final class InMemoryProjectionManager implements ProjectionManager
{
    public const OPTION_CACHE_SIZE = 'cache_size';
    public const OPTION_SLEEP = 'sleep';
    public const OPTION_PERSIST_BLOCK_SIZE = 'persist_block_size';

    private const DEFAULT_CACHE_SIZE = 1000;
    private const DEFAULT_SLEEP = 100000;
    private const DEFAULT_PERSIST_BLOCK_SIZE = 1000;

    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var array
     *
     * key = projection name
     * value = projection instance
     */
    private $projections = [];

    public function __construct(EventStore $eventStore)
    {
        $this->eventStore = $eventStore;

        while ($eventStore instanceof EventStoreDecorator) {
            $eventStore = $eventStore->getInnerEventStore();
        }

        if (! $eventStore instanceof InMemoryEventStore) {
            throw new Exception\InvalidArgumentException('Unknown event store instance given');
        }
    }

    public function createQuery(): Query
    {
        return new InMemoryEventStoreQuery($this->eventStore);
    }

    public function createProjection(
        string $name,
        array $options = null
    ): Projection {
        $projection = new InMemoryEventStoreProjection(
            $this->eventStore,
            $name,
            $options[self::OPTION_CACHE_SIZE] ?? self::DEFAULT_CACHE_SIZE,
            $options[self::OPTION_SLEEP] ?? self::DEFAULT_SLEEP
        );

        if (! isset($this->projections[$name])) {
            $this->projections[$name] = $projection;
        }

        return $projection;
    }

    public function createReadModelProjection(
        string $name,
        ReadModel $readModel,
        array $options = null
    ): ReadModelProjection {
        $projection = new InMemoryEventStoreReadModelProjection(
            $this->eventStore,
            $name,
            $readModel,
            $options[self::OPTION_CACHE_SIZE] ?? self::DEFAULT_CACHE_SIZE,
            $options[self::OPTION_PERSIST_BLOCK_SIZE] ?? self::DEFAULT_PERSIST_BLOCK_SIZE,
            $options[self::OPTION_SLEEP] ?? self::DEFAULT_SLEEP
        );

        if (! isset($this->projections[$name])) {
            $this->projections[$name] = $projection;
        }

        return $projection;
    }

    public function deleteProjection(string $name, bool $deleteEmittedEvents): void
    {
        throw new Exception\RuntimeException('Deleting a projection is not supported in ' . get_class($this));
    }

    public function resetProjection(string $name): void
    {
        throw new Exception\RuntimeException('Resetting a projection is not supported in ' . get_class($this));
    }

    public function stopProjection(string $name): void
    {
        throw new Exception\RuntimeException('Stopping a projection is not supported in ' . get_class($this));
    }

    public function fetchProjectionNames(?string $filter, bool $regex, int $limit, int $offset): array
    {
        if (null === $filter && $regex) {
            throw new Exception\InvalidArgumentException('No regex pattern given');
        }

        if ($regex && false === @preg_match("/$filter/", '')) {
            throw new Exception\InvalidArgumentException('Invalid regex pattern given');
        }

        $result = [];

        $skipped = 0;
        $found = 0;

        $projectionNames = array_keys($this->projections);
        ksort($projectionNames);

        foreach ($projectionNames as $projectionName) {
            if ($regex) {
                if (! preg_match("/$filter/", $projectionName)) {
                    continue;
                }

                $result[] = $projectionName;
                ++$found;
            } elseif (null === $filter || $filter === $projectionName) {
                if ($offset > $skipped) {
                    ++$skipped;
                    continue;
                }

                $result[] = $projectionName;
                ++$found;
            }

            if ($found === $limit) {
                break;
            }
        }

        return $result;
    }

    public function fetchProjectionStatus(string $name): ProjectionStatus
    {
        if (! isset($this->projections[$name])) {
            throw new Exception\RuntimeException('A projection with name "' . $name . '" could not be found.');
        }

        $projection = $this->projections[$name];

        $ref = new \ReflectionProperty(get_class($projection), 'status');
        $ref->setAccessible(true);

        return $ref->getValue($projection);
    }

    public function fetchProjectionStreamPositions(string $name): ?array
    {
        if (! isset($this->projections[$name])) {
            throw new Exception\RuntimeException('A projection with name "' . $name . '" could not be found.');
        }

        $projection = $this->projections[$name];

        $ref = new \ReflectionProperty(get_class($projection), 'streamPositions');
        $ref->setAccessible(true);

        return $ref->getValue($projection);
    }

    public function fetchProjectionState(string $name): array
    {
        if (! isset($this->projections[$name])) {
            throw new Exception\RuntimeException('A projection with name "' . $name . '" could not be found.');
        }

        return $this->projections[$name]->getState();
    }
}
