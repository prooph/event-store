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

namespace Prooph\EventStore;

use ArrayIterator;
use Iterator;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Exception\StreamExistsAlready;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Exception\TransactionAlreadyStarted;
use Prooph\EventStore\Exception\TransactionNotStarted;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Metadata\Operator;
use Prooph\EventStore\Projection\InMemoryEventStoreProjectionFactory;
use Prooph\EventStore\Projection\InMemoryEventStoreQueryFactory;
use Prooph\EventStore\Projection\InMemoryEventStoreReadModelProjectionFactory;
use Prooph\EventStore\Projection\Projection;
use Prooph\EventStore\Projection\ProjectionFactory;
use Prooph\EventStore\Projection\ProjectionOptions;
use Prooph\EventStore\Projection\Query;
use Prooph\EventStore\Projection\QueryFactory;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjection;
use Prooph\EventStore\Projection\ReadModelProjectionFactory;
use Prooph\EventStore\Util\Assertion;

final class InMemoryEventStore implements TransactionalEventStore
{
    /**
     * @var array
     */
    private $streams = [];

    /**
     * @var array
     */
    private $cachedStreams = [];

    /**
     * @var bool
     */
    private $inTransaction = false;

    /**
     * Will be lazy initialized if needed
     *
     * @var QueryFactory
     */
    private $defaultQueryFactory;

    /**
     * Will be lazy initialized if needed
     *
     * @var ProjectionFactory
     */
    private $defaultProjectionFactory;

    /**
     * Will be lazy initialized if needed
     *
     * @var ReadModelProjectionFactory
     */
    private $defaultReadModelProjectionFactory;

    /**
     * @var string[]
     */
    private $projectionNames = [];

    public function create(Stream $stream): void
    {
        $streamName = $stream->streamName();
        $streamNameString = $streamName->toString();

        if (isset($this->streams[$streamNameString])
            || isset($this->cachedStreams[$streamNameString])
        ) {
            throw StreamExistsAlready::with($streamName);
        }

        if ($this->inTransaction) {
            $this->cachedStreams[$streamNameString]['events'] = $stream->streamEvents();
            $this->cachedStreams[$streamNameString]['metadata'] = $stream->metadata();
        } else {
            $this->streams[$streamNameString]['events'] = $stream->streamEvents();
            $this->streams[$streamNameString]['metadata'] = $stream->metadata();
        }
    }

    public function appendTo(StreamName $streamName, Iterator $streamEvents): void
    {
        $streamNameString = $streamName->toString();

        if (! isset($this->streams[$streamNameString])
            && ! isset($this->cachedStreams[$streamNameString])
        ) {
            throw StreamNotFound::with($streamName);
        }

        if ($this->inTransaction) {
            if (! isset($this->cachedStreams[$streamNameString])) {
                $this->cachedStreams[$streamNameString]['events'] = [];
            }

            foreach ($streamEvents as $streamEvent) {
                $this->cachedStreams[$streamNameString]['events'][] = $streamEvent;
            }
        } else {
            foreach ($streamEvents as $streamEvent) {
                $this->streams[$streamNameString]['events'][] = $streamEvent;
            }
        }
    }

    public function load(
        StreamName $streamName,
        int $fromNumber = 1,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Iterator {
        Assertion::greaterOrEqualThan($fromNumber, 1);
        Assertion::nullOrGreaterOrEqualThan($count, 1);

        if (! isset($this->streams[$streamName->toString()])) {
            throw StreamNotFound::with($streamName);
        }

        if (null === $metadataMatcher) {
            $metadataMatcher = new MetadataMatcher();
        }

        $streamEvents = [];

        foreach ($this->streams[$streamName->toString()]['events'] as $key => $streamEvent) {
            /* @var Message $streamEvent */
            if ($this->matchesMetadata($metadataMatcher, $streamEvent->metadata())
                && ((null === $count
                        && ($key + 1) >= $fromNumber
                    ) || (null !== $count
                        && ($key + 1) >= $fromNumber
                        && ($key + 1) <= ($fromNumber + $count - 1)
                    )
                )
            ) {
                $streamEvents[] = $streamEvent;
            }
        }

        return new ArrayIterator($streamEvents);
    }

    public function loadReverse(
        StreamName $streamName,
        int $fromNumber = PHP_INT_MAX,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Iterator {
        Assertion::greaterOrEqualThan($fromNumber, 1);
        Assertion::nullOrGreaterOrEqualThan($count, 1);

        if (! isset($this->streams[$streamName->toString()])) {
            throw StreamNotFound::with($streamName);
        }

        if (null === $metadataMatcher) {
            $metadataMatcher = new MetadataMatcher();
        }

        $streamEvents = [];

        foreach ($this->streams[$streamName->toString()]['events'] as $key => $streamEvent) {
            /* @var Message $streamEvent */
            if ($this->matchesMetadata($metadataMatcher, $streamEvent->metadata())
                && ((null === $count
                        && ($key + 1) <= $fromNumber
                    ) || (null !== $count
                        && ($key + 1) <= $fromNumber
                        && ($key + 1) >= ($fromNumber - $count + 1)
                    )
                )
            ) {
                $streamEvents[] = $streamEvent;
            }
        }

        return new ArrayIterator(array_reverse($streamEvents));
    }

    public function delete(StreamName $streamName): void
    {
        $streamNameString = $streamName->toString();

        if (isset($this->streams[$streamNameString])) {
            unset($this->streams[$streamNameString]);
        } else {
            throw StreamNotFound::with($streamName);
        }
    }

    public function hasStream(StreamName $streamName): bool
    {
        return isset($this->streams[$streamName->toString()]);
    }

    public function fetchStreamMetadata(StreamName $streamName): array
    {
        if (! isset($this->streams[$streamName->toString()])) {
            throw StreamNotFound::with($streamName);
        }

        return $this->streams[$streamName->toString()]['metadata'];
    }

    public function updateStreamMetadata(StreamName $streamName, array $newMetadata): void
    {
        if (! isset($this->streams[$streamName->toString()])) {
            throw StreamNotFound::with($streamName);
        }

        $this->streams[$streamName->toString()]['metadata'] = $newMetadata;
    }

    public function beginTransaction(): void
    {
        if ($this->inTransaction) {
            throw new TransactionAlreadyStarted();
        }

        $this->inTransaction = true;
    }

    public function commit(): void
    {
        if (! $this->inTransaction) {
            throw new TransactionNotStarted();
        }

        foreach ($this->cachedStreams as $streamName => $data) {
            if (isset($data['metadata'])) {
                $this->streams[$streamName] = $data;
            } else {
                foreach ($data['events'] as $streamEvent) {
                    $this->streams[$streamName]['events'][] = $streamEvent;
                }
            }
        }

        $this->cachedStreams = [];
        $this->inTransaction = false;
    }

    public function rollback(): void
    {
        if (! $this->inTransaction) {
            throw new TransactionNotStarted();
        }

        $this->cachedStreams = [];
        $this->inTransaction = false;
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    /**
     * @throws \Exception
     *
     * @return mixed
     */
    public function transactional(callable $callable)
    {
        $this->beginTransaction();

        try {
            $result = $callable($this);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }

        return $result ?: true;
    }

    public function createQuery(QueryFactory $factory = null): Query
    {
        if (null === $factory) {
            $factory = $this->getDefaultQueryFactory();
        }

        return $factory($this);
    }

    public function createProjection(
        string $name,
        ProjectionOptions $options = null,
        ProjectionFactory $factory = null
    ): Projection {
        if (null === $factory) {
            $factory = $this->getDefaultProjectionFactory();
        }

        $projection = $factory($this, $name, $options);

        if (! in_array($name, $this->projectionNames)) {
            $this->projectionNames[] = $name;
        }

        return $projection;
    }

    public function createReadModelProjection(
        string $name,
        ReadModel $readModel,
        ProjectionOptions $options = null,
        ReadModelProjectionFactory $factory = null
    ): ReadModelProjection {
        if (null === $factory) {
            $factory = $this->getDefaultReadModelProjectionFactory();
        }

        $projection = $factory($this, $name, $readModel, $options);

        if (! in_array($name, $this->projectionNames)) {
            $this->projectionNames[] = $name;
        }

        return $projection;
    }

    public function getDefaultQueryFactory(): QueryFactory
    {
        if (null === $this->defaultQueryFactory) {
            $this->defaultQueryFactory = new InMemoryEventStoreQueryFactory();
        }

        return $this->defaultQueryFactory;
    }

    public function getDefaultProjectionFactory(): ProjectionFactory
    {
        if (null === $this->defaultProjectionFactory) {
            $this->defaultProjectionFactory = new InMemoryEventStoreProjectionFactory();
        }

        return $this->defaultProjectionFactory;
    }

    public function getDefaultReadModelProjectionFactory(): ReadModelProjectionFactory
    {
        if (null === $this->defaultReadModelProjectionFactory) {
            $this->defaultReadModelProjectionFactory = new InMemoryEventStoreReadModelProjectionFactory();
        }

        return $this->defaultReadModelProjectionFactory;
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

    public function fetchStreamNames(
        ?string $filter,
        bool $regex,
        ?MetadataMatcher $metadataMatcher,
        int $limit,
        int $offset
    ): array {
        if (null === $filter && $regex) {
            throw new Exception\InvalidArgumentException('No regex pattern given');
        }

        if ($regex && false === @preg_match("/$filter/", '')) {
            throw new Exception\InvalidArgumentException('Invalid regex pattern given');
        }

        $result = [];

        $skipped = 0;
        $found = 0;

        foreach ($this->streams as $streamName => $data) {
            if ($regex) {
                if (! preg_match("/$filter/", $streamName)) {
                    continue;
                }

                if ($metadataMatcher && ! $this->matchesMetadata($metadataMatcher, $data['metadata'])) {
                    continue;
                }

                $result[] = new StreamName($streamName);
                ++$found;
            } elseif (null === $filter || $filter === $streamName) {
                if ($offset > $skipped) {
                    ++$skipped;
                    continue;
                }

                if ($metadataMatcher && ! $this->matchesMetadata($metadataMatcher, $data['metadata'])) {
                    continue;
                }

                $result[] = new StreamName($streamName);
                ++$found;
            }

            if ($found === $limit) {
                break;
            }
        }

        return $result;
    }

    public function fetchCategoryNames(?string $filter, bool $regex, int $limit, int $offset): array
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

        $categories = array_unique(array_reduce(
            array_keys($this->streams),
            function (array $result, string $streamName): array {
                if (preg_match('/(.+)-.+/', $streamName, $matches)) {
                    $result[] = $matches[1];
                }

                return $result;
            },
            []
        ));

        foreach ($categories as $category) {
            if ($regex) {
                if (! preg_match("/$filter/", $category)) {
                    continue;
                }

                $result[] = $category;
                ++$found;
            } elseif (null === $filter || $filter === $category) {
                if ($offset > $skipped) {
                    ++$skipped;
                    continue;
                }

                $result[] = $category;
                ++$found;
            }

            if ($found === $limit) {
                break;
            }
        }

        return $result;
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

        foreach ($this->projectionNames as $projectionName) {
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

    private function matchesMetadata(MetadataMatcher $metadataMatcher, array $metadata): bool
    {
        foreach ($metadataMatcher->data() as $match) {
            $field = $match['field'];

            if (! isset($metadata[$field])) {
                return false;
            }

            $operator = $match['operator'];
            $expected = $match['value'];

            switch ($operator) {
                case Operator::EQUALS():
                    if ($metadata[$field] !== $expected) {
                        return false;
                    }
                    break;
                case Operator::GREATER_THAN():
                    if (! ($metadata[$field] > $expected)) {
                        return false;
                    }
                    break;
                case Operator::GREATER_THAN_EQUALS():
                    if (! ($metadata[$field] >= $expected)) {
                        return false;
                    }
                    break;
                case Operator::LOWER_THAN():
                    if (! ($metadata[$field] < $expected)) {
                        return false;
                    }
                    break;
                case Operator::LOWER_THAN_EQUALS():
                    if (! ($metadata[$field] <= $expected)) {
                        return false;
                    }
                    break;
                case Operator::NOT_EQUALS():
                    if ($metadata[$field] === $expected) {
                        return false;
                    }
                    break;
                default:
                    throw new \UnexpectedValueException('Unknown operator found');
            }
        }

        return true;
    }
}
