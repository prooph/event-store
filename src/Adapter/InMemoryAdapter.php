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
use DateTimeInterface;
use Iterator;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Exception\StreamNotFoundException;
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

    public function create(Stream $stream): void
    {
        $streamEvents = $stream->streamEvents();
        $streamEvents->rewind();
        $this->streams[$stream->streamName()->toString()] = $streamEvents;
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
        $appendIterator->append($this->streams[$streamName->toString()]);
        $appendIterator->append($domainEvents);

        $this->streams[$streamName->toString()] = $appendIterator;
    }

    public function load(
        StreamName $streamName,
        int $fromNumber = 0,
        int $toNumber = null,
        bool $forward = true
    ): ?Stream {
        if (! isset($this->streams[$streamName->toString()])) {
            return null;
        }

        $streamEvents = $this->streams[$streamName->toString()];

        $filteredEvents = [];

        foreach ($streamEvents as $streamEvent) {
            if ($forward) {
                if ($streamEvent->version() >= $fromNumber) {
                    $filteredEvents[] = $streamEvent;
                }
            } else {
                if ($streamEvent->version() <= $fromNumber) {
                    $filteredEvents[] = $streamEvent;
                }
            }
        }

        if (null !== $toNumber) {
            foreach ($filteredEvents as $key => $streamEvent) {
                if ($forward) {
                    if ($streamEvent->version() > $toNumber) {
                        unset($filteredEvents[$key]);
                    }
                } else {
                    if ($streamEvent->version() < $toNumber) {
                        unset($filteredEvents[$key]);
                    }
                }
            }
        }

        if (! $forward) {
            $filteredEvents = array_reverse($filteredEvents);
        }

        return new Stream($streamName, new \ArrayIterator($filteredEvents));
    }

    public function loadEvents(
        StreamName $streamName,
        array $metadata = [],
        int $fromNumber = 0,
        int $toNumber = null,
        bool $forward = true
    ): Iterator {
        if (! isset($this->streams[$streamName->toString()])) {
            return new ArrayIterator();
        }

        $streamEvents = [];

        foreach ($this->streams[$streamName->toString()] as $index => $streamEvent) {
            if ($this->matchMetadataWith($streamEvent, $metadata)) {
                if ($forward) {
                    if ($streamEvent->version() >= $fromNumber) {
                        $streamEvents[$index] = $streamEvent;
                    }
                    if (null !== $toNumber && $streamEvent->version() > $toNumber) {
                        unset($streamEvents[$index]);
                    }
                } else {
                    if ($streamEvent->version() <= $fromNumber) {
                        $streamEvents[$index] = $streamEvent;
                    }
                    if (null !== $toNumber && $streamEvent->version() < $toNumber) {
                        unset($streamEvents[$index]);
                    }
                }
            }
        }

        if (! $forward) {
            $streamEvents = array_reverse($streamEvents);
        }

        return new ArrayIterator($streamEvents);
    }

    private function matchMetadataWith(Message $streamEvent, array $metadata): bool
    {
        if (empty($metadata)) {
            return true;
        }

        $streamEventMetadata = $streamEvent->metadata();

        foreach ($metadata as $key => $value) {
            if (! isset($streamEventMetadata[$key])) {
                return false;
            }

            if ($streamEventMetadata[$key] !== $value) {
                return false;
            }
        }

        return true;
    }
}
