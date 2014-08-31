<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 31.08.14 - 01:55
 */

namespace Prooph\EventStore\Stream;

use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;

/**
 * Class AggregateTypeStreamStrategy
 *
 * This strategy manages the events of aggregates of the same aggregate type in one stream.
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AggregateTypeStreamStrategy implements StreamStrategyInterface
{
    /**
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @var array
     */
    protected $aggregateTypeStreamMap = array();

    /**
     * @param EventStore $eventStore
     * @param array $aggregateTypeStreamMap
     */
    public function __construct(EventStore $eventStore, array $aggregateTypeStreamMap = array())
    {
        $this->eventStore = $eventStore;
        $this->aggregateTypeStreamMap = $aggregateTypeStreamMap;
    }

    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @param StreamEvent[] $streamEvents
     * @return void
     */
    public function register(AggregateType $aggregateType, $aggregateId, array $streamEvents)
    {
        $streamName = $this->buildStreamName($aggregateType);

        \Assert\that($aggregateId)->string('AggregateId needs to be string');

        foreach ( $streamEvents as $index => $streamEvent) {
            $streamEvent->setMetadataEntry('aggregate_id', $aggregateId);
            $streamEvents[$index] = $streamEvent;
        }

        $this->eventStore->appendTo($streamName, $streamEvents);
    }

    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @param StreamEvent[] $streamEvents
     * @return void
     */
    public function appendEvents(AggregateType $aggregateType, $aggregateId, array $streamEvents)
    {
        $streamName = $this->buildStreamName($aggregateType);

        \Assert\that($aggregateId)->string('AggregateId needs to be string');

        foreach ( $streamEvents as $index => $streamEvent) {
            $streamEvent->setMetadataEntry('aggregate_id', $aggregateId);
            $streamEvents[$index] = $streamEvent;
        }

        $this->eventStore->appendTo($streamName, $streamEvents);
    }

    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @return void
     */
    public function remove(AggregateType $aggregateType, $aggregateId)
    {
        $streamName = $this->buildStreamName($aggregateType);

        \Assert\that($aggregateId)->string('AggregateId needs to be string');

        $this->eventStore->removeEventsByMetadataFrom($streamName, array('aggregate_id' => $aggregateId));
    }

    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @return StreamEvent[]
     */
    public function read(AggregateType $aggregateType, $aggregateId)
    {
        $streamName = $this->buildStreamName($aggregateType);

        \Assert\that($aggregateId)->string('AggregateId needs to be string');

        return $this->eventStore->loadEventsByMetadataFrom($streamName, array('aggregate_id' => $aggregateId));
    }

    /**
     * @param AggregateType $aggregateType
     * @return StreamName
     */
    protected function buildStreamName(AggregateType $aggregateType)
    {
        if (isset($this->aggregateTypeStreamMap[$aggregateType->toString()])) {
            return new StreamName($this->aggregateTypeStreamMap[$aggregateType->toString()]);
        }

        return new StreamName($aggregateType->toString());
    }
}
 