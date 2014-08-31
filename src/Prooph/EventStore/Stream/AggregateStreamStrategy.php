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

use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;

/**
 * Class AggregateStreamStrategy
 *
 * This strategy creates a stream for each individual aggregate root.
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AggregateStreamStrategy implements StreamStrategyInterface
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
        $this->eventStore->create(new Stream($this->buildStreamName($aggregateType, $aggregateId), $streamEvents));
    }

    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @param StreamEvent[] $streamEvents
     * @return void
     */
    public function appendEvents(AggregateType $aggregateType, $aggregateId, array $streamEvents)
    {
        $this->eventStore->appendTo($this->buildStreamName($aggregateType, $aggregateId), $streamEvents);
    }

    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @return StreamEvent[]
     */
    public function read(AggregateType $aggregateType, $aggregateId)
    {
        return $this->eventStore->load($this->buildStreamName($aggregateType, $aggregateId));
    }

    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @return StreamName
     */
    protected function buildStreamName(AggregateType $aggregateType, $aggregateId)
    {
        \Assert\that($aggregateId)->string('AggregateId needs to be string');

        $aggregateType = (isset($this->aggregateTypeStreamMap[$aggregateType->toString()]))?
            $this->aggregateTypeStreamMap[$aggregateType->toString()] : $aggregateType->toString();

        return new StreamName($aggregateType . '-' . $aggregateId);
    }
}
 