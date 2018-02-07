<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use ArrayIterator;
use EmptyIterator;
use Iterator;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Exception\StreamExistsAlready;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Exception\TransactionAlreadyStarted;
use Prooph\EventStore\Exception\TransactionNotStarted;
use Prooph\EventStore\Metadata\FieldType;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Metadata\Operator;
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
            $this->cachedStreams[$streamNameString]['events'] = iterator_to_array($stream->streamEvents());
            $this->cachedStreams[$streamNameString]['metadata'] = $stream->metadata();
        } else {
            $this->streams[$streamNameString]['events'] = iterator_to_array($stream->streamEvents());
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

        $found = 0;
        $streamEvents = [];

        foreach ($this->streams[$streamName->toString()]['events'] as $key => $streamEvent) {
            /* @var Message $streamEvent */
            if (($key + 1) >= $fromNumber
                && $this->matchesMetadata($metadataMatcher, $streamEvent->metadata())
                && $this->matchesMessagesProperty($metadataMatcher, $streamEvent)
            ) {
                ++$found;
                $streamEvents[] = $streamEvent;

                if ($found === $count) {
                    break;
                }
            }
        }

        if (0 === $found) {
            return new EmptyIterator();
        }

        return new ArrayIterator($streamEvents);
    }

    public function loadReverse(
        StreamName $streamName,
        int $fromNumber = null,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Iterator {
        if (null === $fromNumber) {
            $fromNumber = PHP_INT_MAX;
        }

        Assertion::greaterOrEqualThan($fromNumber, 1);
        Assertion::nullOrGreaterOrEqualThan($count, 1);

        if (! isset($this->streams[$streamName->toString()])) {
            throw StreamNotFound::with($streamName);
        }

        if (null === $metadataMatcher) {
            $metadataMatcher = new MetadataMatcher();
        }

        $found = 0;
        $streamEvents = [];

        foreach (array_reverse($this->streams[$streamName->toString()]['events'], true) as $key => $streamEvent) {
            /* @var Message $streamEvent */
            if (($key + 1) <= $fromNumber
                && $this->matchesMetadata($metadataMatcher, $streamEvent->metadata())
                && $this->matchesMessagesProperty($metadataMatcher, $streamEvent)
            ) {
                $streamEvents[] = $streamEvent;
                ++$found;

                if ($found === $count) {
                    break;
                }
            }
        }

        if (0 === $found) {
            return new EmptyIterator();
        }

        return new ArrayIterator($streamEvents);
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
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }

        return $result ?: true;
    }

    public function fetchStreamNames(
        ?string $filter,
        ?MetadataMatcher $metadataMatcher,
        int $limit = 20,
        int $offset = 0
    ): array {
        $result = [];

        $skipped = 0;
        $found = 0;

        $streams = $this->streams;

        if ($filter
            && array_key_exists($filter, $streams)
            && (
                ! $metadataMatcher
                || $metadataMatcher && $this->matchesMetadata($metadataMatcher, $streams[$filter]['metadata'])
            )
        ) {
            return [$filter];
        }

        ksort($streams);

        foreach ($streams as $streamName => $data) {
            if (null === $filter || $filter === $streamName) {
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

    public function fetchStreamNamesRegex(
        string $filter,
        ?MetadataMatcher $metadataMatcher,
        int $limit = 20,
        int $offset = 0
    ): array {
        if (false === @preg_match("/$filter/", '')) {
            throw new Exception\InvalidArgumentException('Invalid regex pattern given');
        }

        $result = [];

        $skipped = 0;
        $found = 0;

        $streams = $this->streams;
        ksort($streams);

        foreach ($streams as $streamName => $data) {
            if (! preg_match("/$filter/", $streamName)) {
                continue;
            }

            if ($metadataMatcher && ! $this->matchesMetadata($metadataMatcher, $data['metadata'])) {
                continue;
            }

            $result[] = new StreamName($streamName);
            ++$found;

            if ($found === $limit) {
                break;
            }
        }

        return $result;
    }

    public function fetchCategoryNames(?string $filter, int $limit = 20, int $offset = 0): array
    {
        $result = [];

        $skipped = 0;
        $found = 0;

        $categories = array_unique(array_reduce(
            array_keys($this->streams),
            function (array $result, string $streamName): array {
                if (preg_match('/^(.+)-.+$/', $streamName, $matches)) {
                    $result[] = $matches[1];
                }

                return $result;
            },
            []
        ));

        if ($filter && in_array($filter, $categories, true)) {
            return [$filter];
        }

        ksort($categories);

        foreach ($categories as $category) {
            if (null === $filter || $filter === $category) {
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

    public function fetchCategoryNamesRegex(string $filter, int $limit = 20, int $offset = 0): array
    {
        if (false === @preg_match("/$filter/", '')) {
            throw new Exception\InvalidArgumentException('Invalid regex pattern given');
        }

        $result = [];

        $skipped = 0;
        $found = 0;

        $categories = array_unique(array_reduce(
            array_keys($this->streams),
            function (array $result, string $streamName): array {
                if (preg_match('/^(.+)-.+$/', $streamName, $matches)) {
                    $result[] = $matches[1];
                }

                return $result;
            },
            []
        ));

        ksort($categories);

        foreach ($categories as $category) {
            if (! preg_match("/$filter/", $category)) {
                continue;
            }

            if ($offset > $skipped) {
                ++$skipped;
                continue;
            }

            $result[] = $category;
            ++$found;

            if ($found === $limit) {
                break;
            }
        }

        return $result;
    }

    private function matchesMetadata(MetadataMatcher $metadataMatcher, array $metadata): bool
    {
        foreach ($metadataMatcher->data() as $match) {
            if (! FieldType::METADATA()->is($match['fieldType'])) {
                continue;
            }

            $field = $match['field'];

            if (! isset($metadata[$field])) {
                return false;
            }

            if (! $this->match($match['operator'], $metadata[$field], $match['value'])) {
                return false;
            }
        }

        return true;
    }

    private function matchesMessagesProperty(MetadataMatcher $metadataMatcher, Message $message): bool
    {
        foreach ($metadataMatcher->data() as $match) {
            if (! FieldType::MESSAGE_PROPERTY()->is($match['fieldType'])) {
                continue;
            }

            switch ($match['field']) {
                case 'uuid':
                    $value = $message->uuid()->toString();
                    break;
                case 'message_name':
                case 'messageName':
                    $value = $message->messageName();
                    break;
                case 'created_at':
                case 'createdAt':
                    $value = $message->createdAt()->format('Y-m-d\TH:i:s.u');
                    break;
                default:
                    throw new \UnexpectedValueException(sprintf('Unexpected field "%s" given', $match['field']));
            }

            if (! $this->match($match['operator'], $value, $match['value'])) {
                return false;
            }
        }

        return true;
    }

    private function match(Operator $operator, $value, $expected): bool
    {
        switch ($operator) {
            case Operator::EQUALS():
                if ($value !== $expected) {
                    return false;
                }
                break;
            case Operator::GREATER_THAN():
                if (! ($value > $expected)) {
                    return false;
                }
                break;
            case Operator::GREATER_THAN_EQUALS():
                if (! ($value >= $expected)) {
                    return false;
                }
                break;
            case Operator::IN():
                if (! in_array($value, $expected, true)) {
                    return false;
                }
                break;
            case Operator::LOWER_THAN():
                if (! ($value < $expected)) {
                    return false;
                }
                break;
            case Operator::LOWER_THAN_EQUALS():
                if (! ($value <= $expected)) {
                    return false;
                }
                break;
            case Operator::NOT_EQUALS():
                if ($value === $expected) {
                    return false;
                }
                break;
            case Operator::NOT_IN():
                if (in_array($value, $expected, true)) {
                    return false;
                }
                break;
            case Operator::REGEX():
                if (! preg_match('/' . $expected . '/', $value)) {
                    return false;
                }
                break;
            default:
                throw new \UnexpectedValueException('Unknown operator found');
        }

        return true;
    }
}
