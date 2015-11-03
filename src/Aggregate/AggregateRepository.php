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
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Snapshot\SnapshotStore;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;

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
     * @var AggregateTranslator
     */
    protected $aggregateTranslator;

    /**
     * @var AggregateType
     */
    protected $aggregateType;

    /**
     * @var array
     */
    protected $identityMap = [];

    /**
     * @var SnapshotStore|null
     */
    protected $snapshotStore;

    /**
     * @var StreamName
     */
    protected $streamName;

    /**
     * @var bool
     */
    protected $oneStreamPerAggregate;

    /**
     * @param EventStore $eventStore
     * @param AggregateType $aggregateType
     * @param AggregateTranslator $aggregateTranslator
     * @param SnapshotStore|null $snapshotStore
     * @param StreamName|null $streamName
     * @param bool $oneStreamPerAggregate
     */
    public function __construct(
        EventStore $eventStore,
        AggregateType $aggregateType,
        AggregateTranslator $aggregateTranslator,
        SnapshotStore $snapshotStore = null,
        StreamName $streamName = null,
        $oneStreamPerAggregate = false
    ) {
        $this->eventStore = $eventStore;
        $this->eventStore->getActionEventEmitter()->attachListener('commit.pre', [$this, 'addPendingEventsToStream']);

        $this->aggregateType = $aggregateType;
        $this->aggregateTranslator = $aggregateTranslator;
        $this->snapshotStore = $snapshotStore;
        $this->streamName = $streamName;
        $this->oneStreamPerAggregate = $oneStreamPerAggregate;
    }

    /**
     * Repository acts as listener on EventStore.commit.pre events
     * In the listener method the repository checks its identity map for pending events
     * and appends the events to the event stream.
     */
    public function addPendingEventsToStream()
    {
        foreach ($this->identityMap as $aggregateId => $aggregateRoot) {
            $pendingStreamEvents = $this->aggregateTranslator->extractPendingStreamEvents($aggregateRoot);

            if (count($pendingStreamEvents)) {
                $enrichedEvents = [];

                foreach($pendingStreamEvents as $event) {
                    $enrichedEvents[] = $this->enrichEventMetadata($event, $aggregateId);
                }

                $streamName = $this->determineStreamName($aggregateId);

                $this->eventStore->appendTo($streamName, new ArrayIterator($enrichedEvents));

                unset($this->identityMap[$aggregateId]);
            }
        }
    }

    /**
     * @param object $eventSourcedAggregateRoot
     * @throws Exception\AggregateTypeException
     */
    public function addAggregateRoot($eventSourcedAggregateRoot)
    {
        $this->aggregateType->assert($eventSourcedAggregateRoot);

        $domainEvents = $this->aggregateTranslator->extractPendingStreamEvents($eventSourcedAggregateRoot);

        $aggregateId = $this->aggregateTranslator->extractAggregateId($eventSourcedAggregateRoot);

        $streamName = $this->determineStreamName($aggregateId);

        $enrichedEvents = [];

        foreach($domainEvents as $event) {
            $enrichedEvents[] = $this->enrichEventMetadata($event, $aggregateId);
        }

        if ($this->oneStreamPerAggregate) {
            $stream = new Stream($streamName, new ArrayIterator($enrichedEvents));

            $this->eventStore->create($stream);
        } else {
            $this->eventStore->appendTo($streamName, new ArrayIterator($enrichedEvents));
        }
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

        if ($this->snapshotStore) {
            $eventSourcedAggregateRoot = $this->loadFromSnapshotStore($aggregateId);

            if ($eventSourcedAggregateRoot) {
                //Cache aggregate root in the identity map
                $this->identityMap[$aggregateId] = $eventSourcedAggregateRoot;

                return $eventSourcedAggregateRoot;
            }
        }

        $streamName = $this->determineStreamName($aggregateId);

        $streamEvents = null;

        if ($this->oneStreamPerAggregate) {
            $streamEvents = $this->eventStore->load($streamName)->streamEvents();
        } else {
            $streamEvents = $this->eventStore->loadEventsByMetadataFrom($streamName, [
                'aggregate_type' => $this->aggregateType->toString(),
                'aggregate_id' => $aggregateId
            ]);
        }

        if (!$streamEvents->valid()) {
            return null;
        }

        $eventSourcedAggregateRoot = $this->aggregateTranslator->reconstituteAggregateFromHistory(
            $this->aggregateType,
            $streamEvents
        );

        //Cache aggregate root in the identity map but without pending events
        $this->identityMap[$aggregateId] = $eventSourcedAggregateRoot;

        return $eventSourcedAggregateRoot;
    }

    /**
     * @param object $aggregateRoot
     * @return int
     */
    public function extractAggregateVersion($aggregateRoot)
    {
        return $this->aggregateTranslator->extractAggregateVersion($aggregateRoot);
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

        $streamEvents = $this->streamName->read(
            $this->aggregateType,
            $aggregateId,
            $snapshot->lastVersion() + 1
        );

        if (!$streamEvents->valid()) {
            return $aggregateRoot;
        }

        $this->aggregateTranslator->applyStreamEvents($aggregateRoot, $streamEvents);

        return $aggregateRoot;
    }

    /**
     * Default stream name generation.
     * Override this method in an extending repository to provide a custom name
     *
     * @param string|null $aggregateId
     * @return StreamName
     */
    protected function determineStreamName($aggregateId)
    {
        if ($this->oneStreamPerAggregate) {
            return new StreamName($this->aggregateType->toString() . '-' . $aggregateId);
        }

        if (null === $this->streamName) {
            return new StreamName('event_stream');
        }

        return $this->streamName;
    }

    /**
     * Add aggregate_id and aggregate_type as metadata to $domainEvent
     * Override this method in an extending repository to add more or different metadata.
     *
     * @param Message $domainEvent
     * @param string $aggregateId
     * @return Message
     */
    protected function enrichEventMetadata(Message $domainEvent, $aggregateId)
    {
        $domainEvent = $domainEvent->withAddedMetadata('aggregate_id', $aggregateId);
        return $domainEvent->withAddedMetadata('aggregate_type', $this->aggregateType->toString());
    }
}
