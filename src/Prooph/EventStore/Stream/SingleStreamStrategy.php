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

use Assert\Assertion;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;

/**
 * Class SingleStreamStrategy
 *
 * This strategy manages all events of all aggregates in one stream.
 * It requires global unique identifiers for all aggregate roots, because only the aggregate id is used to
 * fetch related stream events!
 *
 * It can also be used to deal with aggregate hierarchies because the repository aggregate type is completely ignored
 * by this strategy.
 *
 * When writing events the strategy adds the class of the aggregate root as aggregate_type metadata to each event.
 * When the repository asks for the aggregate root type {@see getAggregateRootType} method, the strategy looks it
 * up from the first stream event.
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class SingleStreamStrategy implements StreamStrategy
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
     * @param AggregateType $repositoryAggregateType
     * @param string $aggregateId
     * @param StreamEvent[] $streamEvents
     * @param object $aggregateRoot
     * @return void
     */
    public function addEventsForNewAggregateRoot(AggregateType $repositoryAggregateType, $aggregateId, array $streamEvents, $aggregateRoot)
    {
        Assertion::string($aggregateId, 'AggregateId needs to be string');

        foreach ( $streamEvents as $index => $streamEvent) {
            $streamEvent->setMetadataEntry('aggregate_type', get_class($aggregateRoot));
            $streamEvent->setMetadataEntry('aggregate_id', $aggregateId);
            $streamEvents[$index] = $streamEvent;
        }

        $this->eventStore->appendTo(new StreamName($this->streamName), $streamEvents);
    }

    /**
     * @param AggregateType $repositoryAggregateType
     * @param string $aggregateId
     * @param StreamEvent[] $streamEvents
     * @param object $aggregateRoot
     * @return void
     */
    public function appendEvents(AggregateType $repositoryAggregateType, $aggregateId, array $streamEvents, $aggregateRoot)
    {
        Assertion::string($aggregateId, 'AggregateId needs to be string');

        foreach ( $streamEvents as $index => $streamEvent) {
            $streamEvent->setMetadataEntry('aggregate_type', get_class($aggregateRoot));
            $streamEvent->setMetadataEntry('aggregate_id', $aggregateId);
            $streamEvents[$index] = $streamEvent;
        }

        $this->eventStore->appendTo(new StreamName($this->streamName), $streamEvents);
    }

    /**
     * @param AggregateType $repositoryAggregateType
     * @param string $aggregateId
     * @return StreamEvent[]
     */
    public function read(AggregateType $repositoryAggregateType, $aggregateId)
    {
        Assertion::string($aggregateId, 'AggregateId needs to be string');

        return $this->eventStore->loadEventsByMetadataFrom(
            new StreamName($this->streamName),
            array('aggregate_id' => $aggregateId)
        );
    }

    /**
     * @param AggregateType $repositoryAggregateType
     * @param StreamEvent[] $streamEvents
     * @throws \RuntimeException
     * @return AggregateType
     */
    public function getAggregateRootType(AggregateType $repositoryAggregateType, array &$streamEvents)
    {
        if (count($streamEvents)) {
            $first = $streamEvents[0];

            $metadata = $first->metadata();

            if (isset($metadata['aggregate_type'])) {
                return AggregateType::fromAggregateRootClass($metadata['aggregate_type']);
            }
        }

        throw new \RuntimeException("The reconstitution aggregate type can not be detected");
    }
}
 