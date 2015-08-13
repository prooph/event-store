<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 30.08.14 - 23:41
 */

namespace Prooph\EventStore\Adapter;

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
     * @var array
     */
    protected $streams = array();

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
     * @param Message[] $domainEvents
     * @throws \Prooph\EventStore\Exception\StreamNotFoundException
     * @return void
     */
    public function appendTo(StreamName $streamName, array $domainEvents)
    {
        if (! isset($this->streams[$streamName->toString()])) {
            throw new StreamNotFoundException(
                sprintf(
                    'Stream with name %s cannot be found',
                    $streamName->toString()
                )
            );
        }

        $this->streams[$streamName->toString()] = array_merge($this->streams[$streamName->toString()], $domainEvents);
    }

    /**
     * @param StreamName $streamName
     * @param null|int $minVersion
     * @return Stream|null
     */
    public function load(StreamName $streamName, $minVersion = null)
    {
        if (! isset($this->streams[$streamName->toString()])) {
            return null;
        }

        /** @var $streamEvents Message[] */
        $streamEvents = $this->streams[$streamName->toString()];

        if (!is_null($minVersion)) {
            $filteredEvents = array();

            foreach ($streamEvents as $streamEvent) {
                if ($streamEvent->version() >= $minVersion) {
                    $filteredEvents[] = $streamEvent;
                }
            }

            return new Stream($streamName, $filteredEvents);
        }

        return new Stream($streamName, $streamEvents);
    }

    /**
     * @param StreamName $streamName
     * @param array $metadata
     * @param null|int $minVersion
     * @throws \Prooph\EventStore\Exception\StreamNotFoundException
     * @return Message[]
     */
    public function loadEventsByMetadataFrom(StreamName $streamName, array $metadata, $minVersion = null)
    {
        $streamEvents = array();

        if (! isset($this->streams[$streamName->toString()])) {
            throw new StreamNotFoundException(
                sprintf(
                    'Stream with name %s cannot be found',
                    $streamName->toString()
                )
            );
        }

        foreach ($this->streams[$streamName->toString()] as $index => $streamEvent) {
            if ($this->matchMetadataWith($streamEvent, $metadata)) {
                if (is_null($minVersion) || $streamEvent->version() >= $minVersion) {
                    $streamEvents[] = $streamEvent;
                }
            }
        }

        return $streamEvents;
    }

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
 