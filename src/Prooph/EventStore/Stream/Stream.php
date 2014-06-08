<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 06.06.14 - 22:35
 */

namespace Prooph\EventStore\Stream;

/**
 * Class Stream
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class Stream 
{
    /**
     * @var StreamId
     */
    protected $streamId;

    /**
     * @var AggregateType
     */
    protected $aggregateType;

    /**
     * @var StreamEvent[]
     */
    protected $streamEvents;

    /**
     * @param AggregateType $aggregateType
     * @param StreamId $streamId
     * @param StreamEvent[] $streamEvents
     */
    public function __construct(AggregateType $aggregateType, StreamId $streamId, array $streamEvents)
    {
        \Assert\that($streamEvents)->all()->isInstanceOf('Prooph\EventStore\Stream\StreamEvent');

        $this->streamId = $streamId;

        $this->aggregateType = $aggregateType;

        $this->streamEvents = $streamEvents;
    }

    /**
     * @return StreamId
     */
    public function streamId()
    {
        return $this->streamId;
    }

    /**
     * @return AggregateType
     */
    public function aggregateType()
    {
        return $this->aggregateType;
    }

    /**
     * @return StreamEvent[]
     */
    public function streamEvents()
    {
        return $this->streamEvents;
    }
}
 