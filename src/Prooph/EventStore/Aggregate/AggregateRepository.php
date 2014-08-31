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
        AggregateType $aggregateType = null
    ) {
        $this->eventStore = $eventStore;

        $this->eventStore->getPersistenceEvents()->attach('commit.pre', array($this, 'onPreCommit'));

        $this->aggregateTranslator = $aggregateTranslator;
        $this->streamStrategy = $streamStrategy;
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

        $aggregateType = $this->getAggregateType($anEventSourcedAggregateRoot);

        $aggregateId = $this->aggregateTranslator->extractAggregateId($anEventSourcedAggregateRoot);

        $streamEvents = $this->aggregateTranslator->extractPendingStreamEvents($anEventSourcedAggregateRoot);

        $this->streamStrategy->register($aggregateType, $aggregateId, $streamEvents);
    }

    /**
     * @param AggregateType $anAggregateType
     * @param string $anAggregateId
     * @return object
     */
    public function getAggregateRoot(AggregateType $anAggregateType, $anAggregateId)
    {
        $streamEvents = $this->streamStrategy->read($anAggregateType, $anAggregateId);

        $anEventSourcedAggregateRoot = $this->aggregateTranslator->constructAggregateFromHistory($streamEvents);

        $this->identityMap[$anAggregateId] = $anEventSourcedAggregateRoot;

        return $anEventSourcedAggregateRoot;
    }

    /**
     * @param object $anEventSourcedAggregateRoot
     * @throws Exception\AggregateTypeException
     */
    public function removeAggregateRoot($anEventSourcedAggregateRoot)
    {
        if (! is_object($anEventSourcedAggregateRoot)) {
            throw new AggregateTypeException(
                sprintf(
                    'Invalid aggregate given. Aggregates need to be of type object but type of %s given',
                    gettype($anEventSourcedAggregateRoot)
                )
            );
        }

        $aggregateType = $this->getAggregateType($anEventSourcedAggregateRoot);

        $aggregateId = $this->aggregateTranslator->extractAggregateId($anEventSourcedAggregateRoot);

        $this->streamStrategy->remove($aggregateType, $aggregateId);

        unset($this->identityMap[$aggregateId]);
    }

    public function onPreCommit()
    {
        foreach ($this->identityMap as $eventSourcedAggregateRoot) {

            $pendingStreamEvents = $this->aggregateTranslator->extractPendingStreamEvents($eventSourcedAggregateRoot);

            if (count($pendingStreamEvents)) {

                $aggregateType = $this->getAggregateType($eventSourcedAggregateRoot);

                $this->streamStrategy->appendEvents(
                    $aggregateType,
                    $this->aggregateTranslator->extractAggregateId($eventSourcedAggregateRoot),
                    $pendingStreamEvents
                );
            }
        }
    }

    /**
     * @param $eventSourcedAggregateRoot
     * @return AggregateType
     */
    protected function getAggregateType($eventSourcedAggregateRoot)
    {
        if (! is_null($this->aggregateType)) {
            return $this->aggregateType;
        }

        if ($eventSourcedAggregateRoot instanceof AggregateTypeProviderInterface) {
            return $eventSourcedAggregateRoot->aggregateType();
        }

        return new AggregateType(get_class($eventSourcedAggregateRoot));
    }
}
 