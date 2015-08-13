<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 31.08.14 - 02:13
 */

namespace Prooph\EventStore\Stream;

use Assert\Assertion;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;

/**
 * Class AggregateStreamStrategy
 *
 * This strategy creates a stream for each individual aggregate root.
 * The AggregateType provided by the repository must be the same as the class of the AggregateRoot.
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AggregateStreamStrategy implements StreamStrategy
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

        $this->eventStore->create(new Stream($this->buildStreamName($repositoryAggregateType, $aggregateId), $streamEvents));
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

        $this->eventStore->appendTo($this->buildStreamName($repositoryAggregateType, $aggregateId), $streamEvents);
    }

    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @param null|int $minVersion
     * @return Message[]
     */
    public function read(AggregateType $aggregateType, $aggregateId, $minVersion = null)
    {
        $stream = $this->eventStore->load($this->buildStreamName($aggregateType, $aggregateId), $minVersion);

        return $stream->streamEvents();
    }

    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @return StreamName
     */
    protected function buildStreamName(AggregateType $aggregateType, $aggregateId)
    {
        Assertion::string($aggregateId, 'AggregateId needs to be string');

        $aggregateType = (isset($this->aggregateTypeStreamMap[$aggregateType->toString()]))?
            $this->aggregateTypeStreamMap[$aggregateType->toString()] : $aggregateType->toString();

        return new StreamName($aggregateType . '-' . $aggregateId);
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
 