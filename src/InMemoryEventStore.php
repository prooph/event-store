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
use Prooph\EventStore\Projection\InMemoryEventStoreProjection;
use Prooph\EventStore\Projection\InMemoryEventStoreQuery;
use Prooph\EventStore\Projection\InMemoryEventStoreReadModelProjection;
use Prooph\EventStore\Projection\Projection;
use Prooph\EventStore\Projection\ProjectionOptions;
use Prooph\EventStore\Projection\Query;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjection;
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
    private $isInTransaction = false;

    public function create(Stream $stream): void
    {
        $streamName = $stream->streamName();
        $streamNameString = $streamName->toString();

        if (isset($this->streams[$streamNameString])
            || isset($this->cachedStreams[$streamNameString])
        ) {
            throw StreamExistsAlready::with($streamName);
        }

        if ($this->isInTransaction) {
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

        if ($this->isInTransaction) {
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
    ): Stream {
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

        if (empty($streamEvents)) {
            throw StreamNotFound::with($streamName);
        }

        return new Stream(
            $streamName,
            new ArrayIterator($streamEvents),
            $this->streams[$streamName->toString()]['metadata']
        );
    }

    public function loadReverse(
        StreamName $streamName,
        int $fromNumber = PHP_INT_MAX,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Stream {
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

        if (empty($streamEvents)) {
            throw StreamNotFound::with($streamName);
        }

        $streamEvents = array_reverse($streamEvents);

        return new Stream(
            $streamName,
            new ArrayIterator($streamEvents),
            $this->streams[$streamName->toString()]['metadata']
        );
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
        if ($this->isInTransaction) {
            throw new TransactionAlreadyStarted();
        }

        $this->isInTransaction = true;
    }

    public function commit(): void
    {
        if (! $this->isInTransaction) {
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
        $this->isInTransaction = false;
    }

    public function rollback(): void
    {
        if (! $this->isInTransaction) {
            throw new TransactionNotStarted();
        }

        $this->cachedStreams = [];
        $this->isInTransaction = false;
    }

    public function isInTransaction(): bool
    {
        return $this->isInTransaction;
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

    public function createQuery(): Query
    {
        return new InMemoryEventStoreQuery($this);
    }

    public function createProjection(string $name, ProjectionOptions $options = null): Projection
    {
        if (null === $options) {
            $options = new ProjectionOptions();
        }

        return new InMemoryEventStoreProjection(
            $this,
            $name,
            $options->cacheSize(),
            $options->persistBlockSize()
        );
    }

    public function createReadModelProjection(string $name, ReadModel $readModel, ProjectionOptions $options = null): ReadModelProjection
    {
        if (null === $options) {
            $options = new ProjectionOptions();
        }

        return new InMemoryEventStoreReadModelProjection(
            $this,
            $name,
            $readModel,
            $options->cacheSize(),
            $options->persistBlockSize()
        );
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
