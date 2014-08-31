<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 31.08.14 - 02:16
 */

namespace Prooph\EventStore\Stream;

use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;

/**
 * Class SingleStreamStrategy
 *
 * This strategy manages all events of all aggregates in one stream
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class SingleStreamStrategy implements StreamStrategyInterface
{
    /**
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @var string
     */
    protected $streamName = 'event_stream';

    /**
     * @param EventStore $eventStore
     * @param null|string $streamName
     */
    public function __construct(EventStore $eventStore, $streamName = null)
    {
        $this->eventStore = $eventStore;

        if (is_string($streamName)) {
            $this->streamName = $streamName;
        }
    }

    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @param StreamEvent[] $streamEvents
     * @return void
     */
    public function add(AggregateType $aggregateType, $aggregateId, array $streamEvents)
    {
        \Assert\that($aggregateId)->string('AggregateId needs to be string');

        foreach ( $streamEvents as $index => $streamEvent) {
            $streamEvent->setMetadataEntry('aggregate_type', $aggregateType->toString());
            $streamEvent->setMetadataEntry('aggregate_id', $aggregateId);
            $streamEvents[$index] = $streamEvent;
        }

        $this->eventStore->appendTo(new StreamName($this->streamName), $streamEvents);
    }

    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @param StreamEvent[] $streamEvents
     * @return void
     */
    public function appendEvents(AggregateType $aggregateType, $aggregateId, array $streamEvents)
    {
        \Assert\that($aggregateId)->string('AggregateId needs to be string');

        foreach ( $streamEvents as $index => $streamEvent) {
            $streamEvent->setMetadataEntry('aggregate_type', $aggregateType->toString());
            $streamEvent->setMetadataEntry('aggregate_id', $aggregateId);
            $streamEvents[$index] = $streamEvent;
        }

        $this->eventStore->appendTo(new StreamName($this->streamName), $streamEvents);
    }

    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @return StreamEvent[]
     */
    public function read(AggregateType $aggregateType, $aggregateId)
    {
        \Assert\that($aggregateId)->string('AggregateId needs to be string');

        return $this->eventStore->loadEventsByMetadataFrom(
            new StreamName($this->streamName),
            array('aggregate_type' => $aggregateType->toString(), 'aggregate_id' => $aggregateId)
        );
    }
}
 