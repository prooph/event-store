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

use Prooph\EventStore\Aggregate\Exception\AggregateTypeException;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\StreamStrategyInterface;

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
     * @var StreamStrategyInterface
     */
    protected $streamStrategy;

    /**
     * @var AggregateTranslatorInterface
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
     * @param AggregateTranslatorInterface $aggregateTranslator
     * @param StreamStrategyInterface $streamStrategy
     * @param AggregateType $aggregateType
     */
    public function __construct(
        EventStore $eventStore,
        AggregateTranslatorInterface $aggregateTranslator,
        StreamStrategyInterface $streamStrategy,
        AggregateType $aggregateType
    ) {
        $this->eventStore = $eventStore;

        $this->eventStore->getPersistenceEvents()->attach('commit.pre', array($this, 'onPreCommit'));

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

        $streamEvents = $this->aggregateTranslator->extractPendingStreamEvents($anEventSourcedAggregateRoot);

        $this->streamStrategy->add($this->aggregateType, $aggregateId, $streamEvents);
    }

    /**
     * @param string $anAggregateId
     * @return object
     */
    public function getAggregateRoot($anAggregateId)
    {
        $streamEvents = $this->streamStrategy->read($this->aggregateType, $anAggregateId);

        $anEventSourcedAggregateRoot = $this->aggregateTranslator->reconstituteAggregateFromHistory(
            $this->aggregateType,
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
                    $pendingStreamEvents
                );
            }
        }
    }
}
 