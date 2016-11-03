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

namespace Prooph\EventStore\Adapter;

use AppendIterator;
use ArrayIterator;
use Iterator;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Exception\StreamNotFoundException;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Metadata\Operator;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;

/**
 * Class InMemoryAdapter
 *
 * @package Prooph\EventStore\Adapter
 * @author Alexander Miertsch <contact@prooph.de>
 */
class InMemoryAdapter implements Adapter
{
    /**
     * @var Iterator[]
     */
    protected $streams;

    public function fetchStreamMetadata(StreamName $streamName): ?array
    {
        if (isset($this->streams[$streamName->toString()]['metadata'])) {
            return $this->streams[$streamName->toString()]['metadata'];
        }

        return null;
    }

    public function create(Stream $stream): void
    {
        $streamEvents = $stream->streamEvents();
        $streamEvents->rewind();
        $this->streams[$stream->streamName()->toString()]['events'] = $streamEvents;
        $this->streams[$stream->streamName()->toString()]['metadata'] = $stream->metadata();
    }

    /**
     * @throws StreamNotFoundException
     */
    public function appendTo(StreamName $streamName, Iterator $domainEvents): void
    {
        if (! isset($this->streams[$streamName->toString()])) {
            throw StreamNotFoundException::with($streamName);
        }

        $appendIterator = new AppendIterator();
        $appendIterator->append($this->streams[$streamName->toString()]['events']);
        $appendIterator->append($domainEvents);

        $this->streams[$streamName->toString()]['events'] = $appendIterator;
    }

    public function load(
        StreamName $streamName,
        int $fromNumber = 0,
        int $count = null
    ): ?Stream {
        if (! isset($this->streams[$streamName->toString()])) {
            return null;
        }

        $streamEvents = $this->streams[$streamName->toString()]['events'];

        $filteredEvents = [];

        foreach ($streamEvents as $streamEvent) {
            if ((null === $count
                    && $streamEvent->version() >= $fromNumber
                ) || (null !== $count
                    && $streamEvent->version() >= $fromNumber
                    && $streamEvent->version() <= ($fromNumber + $count - 1)
                )
            ) {
                $filteredEvents[] = $streamEvent;
            }
        }

        return new Stream(
            $streamName,
            new \ArrayIterator($filteredEvents),
            $this->streams[$streamName->toString()]['metadata']
        );
    }

    public function loadReverse(
        StreamName $streamName,
        int $fromNumber = 0,
        int $count = null
    ): ?Stream {
        if (! isset($this->streams[$streamName->toString()])) {
            return null;
        }

        $streamEvents = $this->streams[$streamName->toString()]['events'];

        $filteredEvents = [];

        foreach ($streamEvents as $streamEvent) {
            if ((null === $count
                    && $streamEvent->version() <= $fromNumber
                )
                || (null !== $count
                    && $streamEvent->version() <= $fromNumber
                    && $streamEvent->version() >= ($fromNumber - $count + 1)
                )
            ) {
                $filteredEvents[] = $streamEvent;
            }
        }

        $filteredEvents = array_reverse($filteredEvents);

        return new Stream(
            $streamName,
            new \ArrayIterator($filteredEvents),
            $this->streams[$streamName->toString()]['metadata']
        );
    }

    public function loadEvents(
        StreamName $streamName,
        int $fromNumber = 0,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Iterator {
        if (! isset($this->streams[$streamName->toString()])) {
            return new ArrayIterator();
        }

        if (null === $metadataMatcher) {
            $metadataMatcher = new MetadataMatcher();
        }

        $streamEvents = [];

        foreach ($this->streams[$streamName->toString()]['events'] as $index => $streamEvent) {
            if ($this->matchesMetadata($metadataMatcher, $streamEvent->metadata())
                && ((null === $count
                        && $streamEvent->version() >= $fromNumber
                    ) || (null !== $count
                        && $streamEvent->version() < ($fromNumber + $count - 1)
                    )
                )
            ) {
                $streamEvents[] = $streamEvent;
            }
        }

        return new ArrayIterator($streamEvents);
    }

    public function loadEventsReverse(
        StreamName $streamName,
        int $fromNumber = 0,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Iterator {
        if (! isset($this->streams[$streamName->toString()])) {
            return new ArrayIterator();
        }

        if (null === $metadataMatcher) {
            $metadataMatcher = new MetadataMatcher();
        }

        $streamEvents = [];

        foreach ($this->streams[$streamName->toString()]['events'] as $index => $streamEvent) {
            if ($this->matchesMetadata($metadataMatcher, $streamEvent->metadata())
                && ((null === $count
                        && $streamEvent->version() <= $fromNumber
                    ) || (null !== $count
                        && $streamEvent->version() <= ($fromNumber - $count + 1)
                    )
                )
            ) {
                $streamEvents[] = $streamEvent;
            }
        }

        $streamEvents = array_reverse($streamEvents);

        return new ArrayIterator($streamEvents);
    }

    private function matchesMetadata(MetadataMatcher $metadataMatcher, array $metadata): bool
    {
        foreach ($metadataMatcher->data() as $key => $value) {
            if (! isset($metadata[$key])) {
                return false;
            }

            $testValue = $metadataMatcher->data()[$key]['value'];

            switch ($metadataMatcher->data()[$key]['operator']) {
                case Operator::EQUALS():
                    if ($testValue != $value) {
                        return false;
                    }
                    break;
                case Operator::GREATER_THAN():
                    if ($testValue <= $value) {
                        return false;
                    }
                    break;
                case Operator::GREATER_THAN_EQUALS():
                    if ($testValue < $value) {
                        return false;
                    };
                    break;
                case Operator::LOWER_THAN():
                    if ($testValue >= $value) {
                        return false;
                    }
                    break;
                case Operator::LOWER_THAN_EQUALS():
                    if ($testValue > $value) {
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
