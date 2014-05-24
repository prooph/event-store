<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStore\Repository;

use Prooph\EventStore\EventStore;
use Prooph\EventStore\EventSourcing\EventSourcedAggregateRoot;
use Prooph\EventStore\Exception\InvalidArgumentException;

/**
 *  EventSourcingRepository
 * 
 * @author Alexander Miertsch <contact@prooph.de>
 * @package Prooph\EventStore\Repository
 */
class EventSourcingRepository implements RepositoryInterface
{
    /**
     * The EventStore instance
     * 
     * @var EventStore 
     */
    protected $eventStore;
    
    /**
     * Type of the EventSourcedAggregateRoot for that the repository is responsible
     * 
     * @var string
     */
    protected $aggregateType;


    /**
     * @param EventStore $eventStore
     * @param string $aggregateType
     */
    public function __construct(EventStore $eventStore, $aggregateType)
    {
        $this->eventStore = $eventStore;
        $this->aggregateType = $aggregateType;
    }

    /**
     * Add an EventSourcedAggregateRoot
     *
     * @param EventSourcedAggregateRoot $anEventSourcedAggregateRoot
     * @throws \Prooph\EventStore\Exception\InvalidArgumentException If AggregateRoot FQCN does not match
     * @return void
     */
    public function add(EventSourcedAggregateRoot $anEventSourcedAggregateRoot)
    {
        try {
            \Assert\that($anEventSourcedAggregateRoot)->isInstanceOf($this->aggregateType);
        } catch (\InvalidArgumentException $ex) {
            throw new InvalidArgumentException($ex->getMessage());
        }

        $this->eventStore->attach($anEventSourcedAggregateRoot);
    }

    /**
     * Get an EventSourcedAggregateRoot by it's id
     *
     * @param mixed $anAggregateId
     *
     * @return EventSourcedAggregateRoot|null
     */
    public function get($anAggregateId)
    {
        return $this->eventStore->find($this->aggregateType, $anAggregateId);
    }

    /**
     * Remove an EventSourcedAggregateRoot
     *
     * @param \Prooph\EventStore\EventSourcing\EventSourcedAggregateRoot $anEventSourcedAggregateRoot
     * @return void
     */
    public function remove(EventSourcedAggregateRoot $anEventSourcedAggregateRoot)
    {
        $this->eventStore->detach($anEventSourcedAggregateRoot);
    }

    //@TODO: add method removeAll
}
