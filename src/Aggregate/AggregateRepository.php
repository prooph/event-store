<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 31.08.14 - 00:25
 */

namespace Prooph\EventStore\Aggregate;

use Assert\Assertion;
use Prooph\EventStore\Aggregate\Exception\AggregateTypeException;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\StreamStrategy;

/**
 * Class AggregateRepository
 *
 * @package Prooph\EventStore\Aggregate
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AggregateRepository
{
    /**
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @var StreamStrategy
     */
    protected $streamStrategy;

    /**
     * @var AggregateTranslator
     */
    protected $aggregateTranslator;

    /**
     * @var array
     */
    protected $identityMap = array();

    /**
     * @var AggregateType
     */
    protected $aggregateType;

    /**
     * @param EventStore $eventStore
     * @param AggregateTranslator $aggregateTranslator
     * @param StreamStrategy $streamStrategy
     * @param AggregateType $aggregateType
     */
    public function __construct(
        EventStore $eventStore,
        AggregateTranslator $aggregateTranslator,
        StreamStrategy $streamStrategy,
        AggregateType $aggregateType
    ) {
        $this->eventStore = $eventStore;

        $this->eventStore->getActionEventEmitter()->attachListener('commit.pre', $this);

        $this->aggregateTranslator = $aggregateTranslator;
        $this->streamStrategy = $streamStrategy;
        $this->aggregateType = $aggregateType;
    }

    /**
     * Repository acts as listener on EventStore.commit.pre events
     * In the listener method the repository checks its identity map for pending events
     * and appends the events to the event stream.
     */
    public function __invoke()
    {
        foreach ($this->identityMap as $eventSourcedAggregateRoot) {

            $pendingStreamEvents = $this->aggregateTranslator->extractPendingStreamEvents($eventSourcedAggregateRoot);

            if (count($pendingStreamEvents)) {

                $this->streamStrategy->appendEvents(
                    $this->aggregateType,
                    $this->aggregateTranslator->extractAggregateId($eventSourcedAggregateRoot),
                    $pendingStreamEvents,
                    $eventSourcedAggregateRoot
                );
            }
        }
    }

    /**
     * @param object $eventSourcedAggregateRoot
     * @throws Exception\AggregateTypeException
     */
    public function addAggregateRoot($eventSourcedAggregateRoot)
    {
        if (! is_object($eventSourcedAggregateRoot)) {
            throw new AggregateTypeException(
                sprintf(
                    'Invalid aggregate given. Aggregates need to be of type object but type of %s given',
                    gettype($eventSourcedAggregateRoot)
                )
            );
        }

        $aggregateId = $this->aggregateTranslator->extractAggregateId($eventSourcedAggregateRoot);

        $domainEvents = $this->aggregateTranslator->extractPendingStreamEvents($eventSourcedAggregateRoot);

        $this->streamStrategy->addEventsForNewAggregateRoot($this->aggregateType, $aggregateId, $domainEvents, $eventSourcedAggregateRoot);

        $this->identityMap[$aggregateId] = $eventSourcedAggregateRoot;
    }

    /**
     * Returns null if no stream events can be found for aggregate root otherwise the reconstituted aggregate root
     *
     * @param string $aggregateId
     * @return null|object
     */
    public function getAggregateRoot($aggregateId)
    {
        Assertion::string($aggregateId, 'AggregateId needs to be string');

        if (isset($this->identityMap[$aggregateId])) {
            return $this->identityMap[$aggregateId];
        }

        $streamEvents = $this->streamStrategy->read($this->aggregateType, $aggregateId);

        if (count($streamEvents) === 0) {
            return null;
        }

        $aggregateType = $this->streamStrategy->getAggregateRootType($this->aggregateType, $streamEvents);

        $eventSourcedAggregateRoot = $this->aggregateTranslator->reconstituteAggregateFromHistory(
            $aggregateType,
            $streamEvents
        );

        $this->identityMap[$aggregateId] = $eventSourcedAggregateRoot;

        return $eventSourcedAggregateRoot;
    }
}
 