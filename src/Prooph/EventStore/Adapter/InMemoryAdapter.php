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

use Prooph\EventStore\Exception\StreamNotFoundException;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamEvent;
use Prooph\EventStore\Stream\StreamName;

/**
 * Class InMemoryAdapter
 *
 * @package Prooph\EventStore\Adapter
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class InMemoryAdapter implements AdapterInterface
{
    /**
     * @var Stream[]
     */
    protected $streams = array();

    /**
     * @param Stream $aStream
     * @return void
     */
    public function create(Stream $aStream)
    {
        $this->streams[$aStream->streamName()->toString()] = $aStream->streamEvents();
    }

    /**
     * @param StreamName $aStreamName
     * @param array $streamEvents
     * @throws \Prooph\EventStore\Exception\StreamNotFoundException
     * @return void
     */
    public function appendTo(StreamName $aStreamName, array $streamEvents)
    {
        if (! isset($this->streams[$aStreamName->toString()])) {
            throw new StreamNotFoundException(
                sprintf(
                    'Stream with name %s cannot be found',
                    $aStreamName->toString()
                )
            );
        }

        $this->streams[$aStreamName->toString()] = array_merge($this->streams[$aStreamName->toString()], $streamEvents);
    }

    /**
     * @param StreamName $aStreamName
     * @return Stream|null
     */
    public function load(StreamName $aStreamName)
    {
        if (! isset($this->streams[$aStreamName->toString()])) {
            return null;
        }

        $streamEvents = $this->streams[$aStreamName->toString()];

        return new Stream($aStreamName, $streamEvents);
    }

    /**
     * @param StreamName $aStreamName
     * @param array $metadata
     * @throws \Prooph\EventStore\Exception\StreamNotFoundException
     * @return StreamEvent[]
     */
    public function loadEventsByMetadataFrom(StreamName $aStreamName, array $metadata)
    {
        $streamEvents = array();

        if (! isset($this->streams[$aStreamName->toString()])) {
            throw new StreamNotFoundException(
                sprintf(
                    'Stream with name %s cannot be found',
                    $aStreamName->toString()
                )
            );
        }

        foreach ($this->streams[$aStreamName->toString()] as $index => $streamEvent) {
            if ($this->matchMetadataWith($streamEvent, $metadata)) {
                $streamEvents[] = $streamEvent;
            }
        }

        return $streamEvents;
    }

    protected function matchMetadataWith(StreamEvent $streamEvent, array $metadata)
    {
        if (empty($metadata)) {
            return false;
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
 