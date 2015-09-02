<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 08/31/14 - 02:16 AM
 */

namespace Prooph\EventStore\Stream;

use Assert\Assertion;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception;

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
     * @var string|StreamName
     */
    protected $streamName = 'event_stream';

    /**
     * @param EventStore $eventStore
     * @param null|string $streamName
     */
    public function __construct(EventStore $eventStore, $streamName = null)
    {
        $this->eventStore = $eventStore;

        if (is_null($streamName)) {
            $streamName = $this->streamName;
        }

        $this->streamName = new StreamName($streamName);
    }

    /**
     * @param AggregateType $repositoryAggregateType
     * @param string $aggregateId
     * @param Message[] $streamEvents
     * @param object $aggregateRoot
     * @return void
     */
    public function addEventsForNewAggregateRoot(AggregateType $repositoryAggregateType, $aggregateId, array $streamEvents, $aggregateRoot)
    {
        Assertion::string($aggregateId, 'AggregateId needs to be string');

        foreach ($streamEvents as &$streamEvent) {
            $streamEvent = $streamEvent->withAddedMetadata('aggregate_id', $aggregateId);
            $streamEvent = $streamEvent->withAddedMetadata('aggregate_type', get_class($aggregateRoot));
        }

        $this->eventStore->appendTo($this->streamName, $streamEvents);
    }

    /**
     * @param AggregateType $repositoryAggregateType
     * @param string $aggregateId
     * @param Message[] $streamEvents
     * @param object $aggregateRoot
     * @return void
     */
    public function appendEvents(AggregateType $repositoryAggregateType, $aggregateId, array $streamEvents, $aggregateRoot)
    {
        Assertion::string($aggregateId, 'AggregateId needs to be string');

        foreach ($streamEvents as &$streamEvent) {
            $streamEvent = $streamEvent->withAddedMetadata('aggregate_id', $aggregateId);
            $streamEvent = $streamEvent->withAddedMetadata('aggregate_type', get_class($aggregateRoot));
        }

        $this->eventStore->appendTo($this->streamName, $streamEvents);
    }

    /**
     * @param AggregateType $repositoryAggregateType
     * @param string $aggregateId
     * @param null|int $minVersion
     * @return Message[]
     */
    public function read(AggregateType $repositoryAggregateType, $aggregateId, $minVersion = null)
    {
        Assertion::string($aggregateId, 'AggregateId needs to be string');

        return $this->eventStore->loadEventsByMetadataFrom(
            $this->streamName,
            ['aggregate_id' => $aggregateId],
            $minVersion
        );
    }

    /**
     * @param AggregateType $repositoryAggregateType
     * @param Message[] $streamEvents
     * @throws Exception\RuntimeException
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

        throw new Exception\RuntimeException("The aggregate type cannot be detected");
    }
}
