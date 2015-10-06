<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 08/31/14 - 00:25 AM
 */

namespace Prooph\EventStore\Aggregate;

use ArrayIterator;
use Assert\Assertion;
use Prooph\EventStore\Aggregate\Exception\AggregateTypeException;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\SingleStreamStrategy;
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
    protected $identityMap = [];

    /**
     * @var AggregateType
     */
    protected $aggregateType;

    /**
     * @param EventStore $eventStore
     * @param AggregateType $aggregateType
     * @param AggregateTranslator $aggregateTranslator
     * @param StreamStrategy|null $streamStrategy
     */
    public function __construct(
        EventStore $eventStore,
        AggregateType $aggregateType,
        AggregateTranslator $aggregateTranslator,
        StreamStrategy $streamStrategy = null
    ) {
        $this->eventStore = $eventStore;
        $this->eventStore->getActionEventEmitter()->attachListener('commit.pre', [$this, 'addPendingEventsToStream']);
        $this->eventStore->getActionEventEmitter()->attachListener('commit.post', [$this, 'applyPendingStreamEvents']);

        $this->aggregateType = $aggregateType;
        $this->aggregateTranslator = $aggregateTranslator;

        if (null === $streamStrategy) {
            $streamStrategy = new SingleStreamStrategy($this->eventStore);
        }

        $this->streamStrategy = $streamStrategy;
    }

    /**
     * Repository acts as listener on EventStore.commit.pre events
     * In the listener method the repository checks its identity map for pending events
     * and appends the events to the event stream.
     */
    public function addPendingEventsToStream()
    {
        foreach ($this->identityMap as &$identityArr) {

            //If a new aggregate root was added (via method addAggregateRoot) we already have pending events in the cache
            //and don't need to extract them twice
            if ($identityArr['pending_events']) {
                continue;
            }

            $pendingStreamEvents = $this->aggregateTranslator->extractPendingStreamEvents($identityArr['aggregate_root']);

            if (count($pendingStreamEvents)) {
                $this->streamStrategy->appendEvents(
                    $this->aggregateType,
                    $this->aggregateTranslator->extractAggregateId($identityArr['aggregate_root']),
                    new ArrayIterator($pendingStreamEvents),
                    $identityArr['aggregate_root']
                );

                //Cache pending events besides the aggregate root in the identity map
                $identityArr['pending_events'] = $pendingStreamEvents;
            }
        }
    }

    /**
     * Repository acts as listener on EventStore.commit.post events
     * In the listener method the repository checks its identity map for pending events
     * and applies the events to the aggregate roots.
     * Once the events are applied they are removed from the identity map
     */
    public function applyPendingStreamEvents()
    {
        foreach ($this->identityMap as &$identityArr) {
            if ($identityArr['pending_events']) {
                $this->aggregateTranslator->applyPendingStreamEvents(
                    $identityArr['aggregate_root'],
                    $identityArr['pending_events']
                );

                //Clear pending events
                $identityArr['pending_events'] = null;
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

        $domainEvents = $this->aggregateTranslator->extractPendingStreamEvents($eventSourcedAggregateRoot);

        //We make a copy of the aggregate root to be able to extract the aggregate id
        //without the need to apply the pending domain events to the real aggregate instance
        //(this is done in the method applyPendingStreamEvents after EventStore.commit)
        $aggregateRootCopy = $this->aggregateTranslator->reconstituteAggregateFromHistory(
            $this->aggregateType,
            new ArrayIterator($domainEvents)
        );

        $aggregateId = $this->aggregateTranslator->extractAggregateId($aggregateRootCopy);

        $this->streamStrategy->addEventsForNewAggregateRoot(
            $this->aggregateType,
            $aggregateId,
            new ArrayIterator($domainEvents),
            $eventSourcedAggregateRoot
        );

        //Cache the aggregate root together with its pending domain events in the identity map
        $this->identityMap[$aggregateId] = [
            'aggregate_root' => $eventSourcedAggregateRoot,
            'pending_events' => $domainEvents
        ];
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
            return $this->identityMap[$aggregateId]['aggregate_root'];
        }

        $streamEvents = $this->streamStrategy->read($this->aggregateType, $aggregateId);

        if (count($streamEvents) === 0) {
            return;
        }

        $aggregateType = $this->streamStrategy->getAggregateRootType($this->aggregateType, $streamEvents);

        $eventSourcedAggregateRoot = $this->aggregateTranslator->reconstituteAggregateFromHistory(
            $aggregateType,
            $streamEvents
        );

        //Cache aggregate root in the identity map but without pending events
        $this->identityMap[$aggregateId] = [
            'aggregate_root' => $eventSourcedAggregateRoot,
            'pending_events' => null
        ];

        return $eventSourcedAggregateRoot;
    }
}
