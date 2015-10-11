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
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Snapshot\SnapshotStore;
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
     * @var AggregateType
     */
    protected $aggregateType;

    /**
     * @var IdentityMap
     */
    protected $identityMap;

    /**
     * @var SnapshotStore|null
     */
    protected $snapshotStore;

    /**
     * @var array
     */
    protected $pendingEventsMap = [];

    /**
     * @param EventStore $eventStore
     * @param AggregateType $aggregateType
     * @param AggregateTranslator $aggregateTranslator
     * @param StreamStrategy|null $streamStrategy
     * @param IdentityMap|null $identityMap
     * @param SnapshotStore|null $snapshotStore
     */
    public function __construct(
        EventStore $eventStore,
        AggregateType $aggregateType,
        AggregateTranslator $aggregateTranslator,
        StreamStrategy $streamStrategy = null,
        IdentityMap $identityMap = null,
        SnapshotStore $snapshotStore = null
    ) {
        $this->eventStore = $eventStore;
        $this->eventStore->getActionEventEmitter()->attachListener('commit.pre', [$this, 'addPendingEventsToStream']);
        $this->eventStore->getActionEventEmitter()->attachListener('commit.post', [$this, 'applyPendingStreamEvents']);

        $this->aggregateType = $aggregateType;
        $this->aggregateTranslator = $aggregateTranslator;
        $this->snapshotStore = $snapshotStore;

        if (null === $streamStrategy) {
            $streamStrategy = new SingleStreamStrategy($this->eventStore);
        }

        $this->streamStrategy = $streamStrategy;

        if (null === $identityMap) {
            $identityMap = new InMemoryIdentityMap();
        }

        $this->identityMap = $identityMap;
    }

    /**
     * Repository acts as listener on EventStore.commit.pre events
     * In the listener method the repository checks its identity map for pending events
     * and appends the events to the event stream.
     */
    public function addPendingEventsToStream()
    {
        foreach ($this->identityMap->getAllDirtyAggregateRoots($this->aggregateType) as $aggregateId => $aggregateRoot) {

            //If a new aggregate root was added (via method addAggregateRoot) we already have pending events in the cache
            //and don't need to extract them twice
            if (isset($this->pendingEventsMap[$aggregateId])) {
                continue;
            }

            $pendingStreamEvents = $this->aggregateTranslator->extractPendingStreamEvents($aggregateRoot);

            if (count($pendingStreamEvents)) {
                $this->streamStrategy->appendEvents(
                    $this->aggregateType,
                    $aggregateId,
                    new ArrayIterator($pendingStreamEvents),
                    $aggregateRoot
                );

                //Cache pending events as long as event store transaction is not committed successfully
                $this->pendingEventsMap[$aggregateId] = $pendingStreamEvents;
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
        foreach ($this->identityMap->getAllDirtyAggregateRoots($this->aggregateType) as $aggregateId => $aggregateRoot) {
            if (isset($this->pendingEventsMap[$aggregateId])) {
                $this->aggregateTranslator->applyPendingStreamEvents(
                    $aggregateRoot,
                    new ArrayIterator($this->pendingEventsMap[$aggregateId])
                );

                //Clear pending events
                unset($this->pendingEventsMap[$aggregateId]);
            }
        }

        $this->identityMap->cleanUp($this->aggregateType);
    }

    /**
     * @param object $eventSourcedAggregateRoot
     * @throws Exception\AggregateTypeException
     */
    public function addAggregateRoot($eventSourcedAggregateRoot)
    {
        $this->aggregateType->assert($eventSourcedAggregateRoot);

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

        $this->identityMap->add($this->aggregateType, $aggregateId, $eventSourcedAggregateRoot);
        $this->pendingEventsMap[$aggregateId] = $domainEvents;
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

        $eventSourcedAggregateRoot = $this->identityMap->get($this->aggregateType, $aggregateId);

        if ($eventSourcedAggregateRoot) {
            return $eventSourcedAggregateRoot;
        }

        if ($this->snapshotStore) {
            $eventSourcedAggregateRoot = $this->loadFromSnapshotStore($aggregateId);

            if ($eventSourcedAggregateRoot) {
                $this->identityMap->add($this->aggregateType, $aggregateId, $eventSourcedAggregateRoot);

                return $eventSourcedAggregateRoot;
            }
        }

        $streamEvents = $this->streamStrategy->read($this->aggregateType, $aggregateId);

        if (!$streamEvents->valid()) {
            return;
        }

        $aggregateType = $this->streamStrategy->getAggregateRootType($this->aggregateType, $streamEvents);

        $eventSourcedAggregateRoot = $this->aggregateTranslator->reconstituteAggregateFromHistory(
            $aggregateType,
            $streamEvents
        );

        $this->identityMap->add($this->aggregateType, $aggregateId, $eventSourcedAggregateRoot);

        return $eventSourcedAggregateRoot;
    }

    /**
     * @param string $aggregateId
     * @return null|object
     */
    protected function loadFromSnapshotStore($aggregateId)
    {
        $snapshot = $this->snapshotStore->get($this->aggregateType, $aggregateId);

        if (!$snapshot) {
            return;
        }

        $aggregateRoot = $snapshot->aggregateRoot();

        $streamEvents = $this->streamStrategy->read(
            $this->aggregateType,
            $aggregateId,
            $snapshot->lastVersion() + 1
        );

        if (!$streamEvents->valid()) {
            return $aggregateRoot;
        }

        $this->aggregateTranslator->applyPendingStreamEvents($aggregateRoot, $streamEvents);

        return $aggregateRoot;
    }
}
