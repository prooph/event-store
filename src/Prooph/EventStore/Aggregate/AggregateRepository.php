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

        $this->eventStore->getActionEventDispatcher()->attachListener('commit.pre', array($this, 'onPreCommit'));

        $this->aggregateTranslator = $aggregateTranslator;
        $this->streamStrategy = $streamStrategy;
        $this->aggregateType = $aggregateType;
    }

    /**
     * @param object $anEventSourcedAggregateRoot
     * @throws Exception\AggregateTypeException
     */
    public function addAggregateRoot($anEventSourcedAggregateRoot)
    {
        if (! is_object($anEventSourcedAggregateRoot)) {
            throw new AggregateTypeException(
                sprintf(
                    'Invalid aggregate given. Aggregates need to be of type object but type of %s given',
                    gettype($anEventSourcedAggregateRoot)
                )
            );
        }

        $aggregateId = $this->aggregateTranslator->extractAggregateId($anEventSourcedAggregateRoot);

        $domainEvents = $this->aggregateTranslator->extractPendingStreamEvents($anEventSourcedAggregateRoot);

        $this->streamStrategy->addEventsForNewAggregateRoot($this->aggregateType, $aggregateId, $domainEvents, $anEventSourcedAggregateRoot);

        $this->identityMap[$aggregateId] = $anEventSourcedAggregateRoot;
    }

    /**
     * Returns null if no stream events can be found for aggregate root otherwise the reconstituted aggregate root
     *
     * @param string $anAggregateId
     * @return null|object
     */
    public function getAggregateRoot($anAggregateId)
    {
        Assertion::string($anAggregateId, 'AggregateId needs to be string');

        if (isset($this->identityMap[$anAggregateId])) {
            return $this->identityMap[$anAggregateId];
        }

        $streamEvents = $this->streamStrategy->read($this->aggregateType, $anAggregateId);

        if (count($streamEvents) === 0) {
            return null;
        }

        $aggregateType = $this->streamStrategy->getAggregateRootType($this->aggregateType, $streamEvents);

        $anEventSourcedAggregateRoot = $this->aggregateTranslator->reconstituteAggregateFromHistory(
            $aggregateType,
            $streamEvents
        );

        $this->identityMap[$anAggregateId] = $anEventSourcedAggregateRoot;

        return $anEventSourcedAggregateRoot;
    }

    public function onPreCommit()
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
}
 