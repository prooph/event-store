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

use Assert\Assertion;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;

/**
 * Class AggregateTypeStreamStrategy
 *
 * This strategy manages the events of aggregates of the same aggregate type in one stream.
 * The AggregateType provided by the repository must be the same as the class of the AggregateRoot.
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AggregateTypeStreamStrategy implements StreamStrategy
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
     * @param AggregateType $repositoryAggregateType
     * @param string $aggregateId
     * @param StreamEvent[] $streamEvents
     * @param object $aggregateRoot
     * @throws \InvalidArgumentException
     * @return void
     */
    public function addEventsForNewAggregateRoot(AggregateType $repositoryAggregateType, $aggregateId, array $streamEvents, $aggregateRoot)
    {
        $arType = AggregateType::fromAggregateRoot($aggregateRoot);

        if (! $repositoryAggregateType->equals($arType)) {
            throw new \InvalidArgumentException(sprintf('aggregate root mismatch between repository type %s and object type %s', $repositoryAggregateType->toString(), $arType->toString()));
        }

        $streamName = $this->buildStreamName($repositoryAggregateType);

        Assertion::string($aggregateId, 'AggregateId needs to be string');

        foreach ( $streamEvents as $index => $streamEvent) {
            $streamEvent->setMetadataEntry('aggregate_id', $aggregateId);
            $streamEvents[$index] = $streamEvent;
        }

        $this->eventStore->appendTo($streamName, $streamEvents);
    }

    /**
     * @param AggregateType $repositoryAggregateType
     * @param string $aggregateId
     * @param StreamEvent[] $streamEvents
     * @param object $aggregateRoot
     * @throws \InvalidArgumentException
     * @return void
     */
    public function appendEvents(AggregateType $repositoryAggregateType, $aggregateId, array $streamEvents, $aggregateRoot)
    {
        $arType = AggregateType::fromAggregateRoot($aggregateRoot);

        if (! $repositoryAggregateType->equals($arType)) {
            throw new \InvalidArgumentException(sprintf('aggregate root mismatch between repository type %s and object type %s', $repositoryAggregateType->toString(), $arType->toString()));
        }

        $streamName = $this->buildStreamName($repositoryAggregateType);

        Assertion::string($aggregateId, 'AggregateId needs to be string');

        foreach ( $streamEvents as $index => $streamEvent) {
            $streamEvent->setMetadataEntry('aggregate_id', $aggregateId);
            $streamEvents[$index] = $streamEvent;
        }

        $this->eventStore->appendTo($streamName, $streamEvents);
    }

    /**
     * @param AggregateType $repositoryAggregateType
     * @param string $aggregateId
     * @return StreamEvent[]
     */
    public function read(AggregateType $repositoryAggregateType, $aggregateId)
    {
        $streamName = $this->buildStreamName($repositoryAggregateType);

        Assertion::string($aggregateId, 'AggregateId needs to be string');

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

    /**
     * No aggregate type information stored as metadata. The repository aggregate type needs to be used.
     *
     * @param AggregateType $repositoryAggregateType
     * @param StreamEvent[] $streamEvents
     * @return AggregateType
     */
    public function getAggregateRootType(AggregateType $repositoryAggregateType, array &$streamEvents)
    {
        return $repositoryAggregateType;
    }
}
 