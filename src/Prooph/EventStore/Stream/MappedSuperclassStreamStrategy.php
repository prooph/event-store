<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 20.10.14 - 20:26
 */

namespace Prooph\EventStore\Stream;

use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\MappedSuperclass\SuperclassAggregateTypeChanger;

/**
 * Class MappedSuperclassStreamStrategy
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class MappedSuperclassStreamStrategy implements StreamStrategyInterface
{
    /**
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @var StreamName
     */
    protected $streamName;

    /**
     * @var array
     */
    protected $aggregateTypeStreamMap = array();

    /**
     * @var SuperclassAggregateTypeChanger
     */
    protected $superclassAggregateTypeChanger;

    /**
     * @param EventStore $eventStore
     * @param \Prooph\EventStore\Aggregate\AggregateType $superclass
     * @param array $aggregateTypeStreamMap
     */
    public function __construct(EventStore $eventStore, AggregateType $superclass, array $aggregateTypeStreamMap = array())
    {
        $this->eventStore = $eventStore;

        $this->aggregateTypeStreamMap = $aggregateTypeStreamMap;

        $this->streamName = $this->buildStreamName($superclass);
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

        $this->eventStore->appendTo($this->streamName, $streamEvents);
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

        $this->eventStore->appendTo($this->streamName, $streamEvents);
    }

    /**
     * Pass the superclass AggregateType as first argument. It will be converted to the subclass AggregateType
     * after reading the event stream for the aggregate. So you can use the AggregateType to reconstitute the aggregate
     * with the correct subclass in place.
     *
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @return StreamEvent[]
     */
    public function read(AggregateType $aggregateType, $aggregateId)
    {
        \Assert\that($aggregateId)->string('AggregateId needs to be string');

        $events = $this->eventStore->loadEventsByMetadataFrom(
            $this->streamName,
            array('aggregate_id' => $aggregateId)
        );

        if (count($events)) {
            $first = $events[0];

            $metadata = $first->metadata();

            if (isset($metadata['aggregate_type'])) {
                $this->getSuperclassAggregateTypeChanger()->convertToSubclassAggregateType(
                    $aggregateType,
                    $metadata['aggregate_type']
                );
            }
        }

        return $events;
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

    /**
     * @return SuperclassAggregateTypeChanger
     */
    private function getSuperclassAggregateTypeChanger()
    {
        if (is_null($this->superclassAggregateTypeChanger)) {
            $this->superclassAggregateTypeChanger = SuperclassAggregateTypeChanger::fromString("__superclass__");
        }

        return $this->superclassAggregateTypeChanger;
    }
}
 