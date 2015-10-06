<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 08/30/14 - 11:41 PM
 */

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
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class InMemoryAdapter implements Adapter
{
    /**
     * @var Iterator[]
     */
    protected $streams;

    /**
     * @param Stream $stream
     * @return void
     */
    public function create(Stream $stream)
    {
        $this->streams[$stream->streamName()->toString()] = $stream->streamEvents();
    }

    /**
     * @param StreamName $streamName
     * @param Iterator $domainEvents
     * @throws StreamNotFoundException
     * @return void
     */
    public function appendTo(StreamName $streamName, Iterator $domainEvents)
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

    /**
     * @param StreamName $streamName
     * @param null|int $minVersion
     * @return Stream|null
     */
    public function load(StreamName $streamName, $minVersion = null)
    {
        if (! isset($this->streams[$streamName->toString()])) {
            return;
        }

        $streamEvents = $this->streams[$streamName->toString()];

        if (!is_null($minVersion)) {
            $filteredEvents = [];

            foreach ($streamEvents as $streamEvent) {
                if ($streamEvent->version() >= $minVersion) {
                    $filteredEvents[] = $streamEvent;
                }
            }

            return new Stream($streamName, new \ArrayIterator($filteredEvents));
        }

        return new Stream($streamName, $streamEvents);
    }

    /**
     * @param StreamName $streamName
     * @param array $metadata
     * @param null|int $minVersion
     * @throws StreamNotFoundException
     * @return Iterator
     */
    public function loadEventsByMetadataFrom(StreamName $streamName, array $metadata, $minVersion = null)
    {
        if (! isset($this->streams[$streamName->toString()])) {
            return new ArrayIterator();
        }

        $streamEvents = [];

        foreach ($this->streams[$streamName->toString()] as $index => $streamEvent) {
            if ($this->matchMetadataWith($streamEvent, $metadata)) {
                if (is_null($minVersion) || $streamEvent->version() >= $minVersion) {
                    $streamEvents[] = $streamEvent;
                }
            }
        }

        return new ArrayIterator($streamEvents);
    }

    /**
     * @param StreamName $streamName
     * @param DateTimeInterface $since
     * @param array $metadata
     * @return ArrayIterator
     */
    public function replay(StreamName $streamName, DateTimeInterface $since = null, array $metadata)
    {
        if (! isset($this->streams[$streamName->toString()])) {
            return new ArrayIterator();
        }

        $streamEvents = [];

        foreach ($this->streams[$streamName->toString()] as $index => $streamEvent) {
            if (null === $since && $this->matchMetadataWith($streamEvent, $metadata)) {
                $streamEvents[] = $streamEvent;
            } elseif((float) $streamEvent->createdAt()->format('U.u') >= (float) $since->format('U.u')
                && $this->matchMetadataWith($streamEvent, $metadata)
            ) {
                $streamEvents[] = $streamEvent;
            }
        }

        return new ArrayIterator($streamEvents);
    }

    /**
     * @param Message $streamEvent
     * @param array $metadata
     * @return bool
     */
    protected function matchMetadataWith(Message $streamEvent, array $metadata)
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
