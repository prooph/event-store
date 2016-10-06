<?php
/**
 * This file is part of the prooph/service-bus.
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
     * @param StreamName $streamName
     * @param Iterator $domainEvents
     * @throws StreamNotFoundException
     * @return void
     */
    public function appendTo(StreamName $streamName, Iterator $domainEvents): void
    {
        if (! isset($this->streams[$streamName->toString()])) {
            throw new StreamNotFoundException(
                sprintf(
                    'Stream with name %s cannot be found',
                    $streamName->toString()
                )
            );
        }

        $appendIterator = new AppendIterator();
        $appendIterator->append($this->streams[$streamName->toString()]);
        $appendIterator->append($domainEvents);

        $this->streams[$streamName->toString()] = $appendIterator;
    }

    public function load(StreamName $streamName, ?int $minVersion = null): ?Stream
    {
        if (! isset($this->streams[$streamName->toString()])) {
            return null;
        }

        $streamEvents = $this->streams[$streamName->toString()];

        if (null !== $minVersion) {
            $filteredEvents = [];

            foreach ($streamEvents as $streamEvent) {
                if ($streamEvent->version() >= $minVersion) {
                    $filteredEvents[] = $streamEvent;
                }
            }

            return new Stream($streamName, new \ArrayIterator($filteredEvents));
        }

        //Rewind before returning cached iterator as event store uses Iterator::valid()
        //to test if stream contains events
        $streamEvents->rewind();
        return new Stream($streamName, $streamEvents);
    }

    public function loadEvents(StreamName $streamName, array $metadata = [], ?int $minVersion = null): Iterator
    {
        if (! isset($this->streams[$streamName->toString()])) {
            return new ArrayIterator();
        }

        $streamEvents = [];

        foreach ($this->streams[$streamName->toString()] as $index => $streamEvent) {
            if ($this->matchMetadataWith($streamEvent, $metadata)) {
                if (null === $minVersion || $streamEvent->version() >= $minVersion) {
                    $streamEvents[] = $streamEvent;
                }
            }
        }

        return new ArrayIterator($streamEvents);
    }

    public function replay(StreamName $streamName, ?DateTimeInterface $since = null, array $metadata = []): Iterator
    {
        if (! isset($this->streams[$streamName->toString()])) {
            return new ArrayIterator();
        }

        $streamEvents = [];

        foreach ($this->streams[$streamName->toString()] as $index => $streamEvent) {
            if (null === $since && $this->matchMetadataWith($streamEvent, $metadata)) {
                $streamEvents[] = $streamEvent;
            } elseif ($streamEvent->createdAt()->format('U.u') >= $since->format('U.u')
                && $this->matchMetadataWith($streamEvent, $metadata)
            ) {
                $streamEvents[] = $streamEvent;
            }
        }

        return new ArrayIterator($streamEvents);
    }

    protected function matchMetadataWith(Message $streamEvent, array $metadata): bool
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
