<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 31.08.14 - 01:55
 */

namespace Prooph\EventStore\Stream;

use Assert\Assertion;
use Prooph\Common\Messaging\Message;
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
    protected $aggregateTypeStreamMap = [];

    /**
     * @param EventStore $eventStore
     * @param array $aggregateTypeStreamMap
     */
    public function __construct(EventStore $eventStore, array $aggregateTypeStreamMap = [])
    {
        $this->eventStore = $eventStore;
        $this->aggregateTypeStreamMap = $aggregateTypeStreamMap;
    }

    /**
     * @param AggregateType $repositoryAggregateType
     * @param string $aggregateId
     * @param Message[] $streamEvents
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

        foreach ($streamEvents as &$streamEvent) {
            $streamEvent = $streamEvent->withAddedMetadata('aggregate_id', $aggregateId);
        }

        $this->eventStore->appendTo($streamName, $streamEvents);
    }

    /**
     * @param AggregateType $repositoryAggregateType
     * @param string $aggregateId
     * @param Message[] $streamEvents
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

        foreach ($streamEvents as &$streamEvent) {
            $streamEvent = $streamEvent->withAddedMetadata('aggregate_id', $aggregateId);
        }

        $this->eventStore->appendTo($streamName, $streamEvents);
    }

    /**
     * @param AggregateType $repositoryAggregateType
     * @param string $aggregateId
     * @param null|int $minVersion
     * @return Message[]
     */
    public function read(AggregateType $repositoryAggregateType, $aggregateId, $minVersion = null)
    {
        $streamName = $this->buildStreamName($repositoryAggregateType);

        Assertion::string($aggregateId, 'AggregateId needs to be string');

        return $this->eventStore->loadEventsByMetadataFrom($streamName, ['aggregate_id' => $aggregateId], $minVersion);
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
     * @param Message[] $streamEvents
     * @return AggregateType
     */
    public function getAggregateRootType(AggregateType $repositoryAggregateType, array &$streamEvents)
    {
        return $repositoryAggregateType;
    }
}
