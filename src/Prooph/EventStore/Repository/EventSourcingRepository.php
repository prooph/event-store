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
     * FQCN of the EventSourcedAggregateRoot class for that the repository is responsible
     * 
     * @var string
     */
    protected $aggregateFQCN;


    /**
     * @param EventStore $eventStore
     * @param string $aggregateFQCN
     */
    public function __construct(EventStore $eventStore, $aggregateFQCN)
    {
        $this->eventStore = $eventStore;
        $this->aggregateFQCN = $aggregateFQCN;
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
            \Assert\that($anEventSourcedAggregateRoot)->isInstanceOf($this->aggregateFQCN);
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
        return $this->eventStore->find($this->aggregateFQCN, $anAggregateId);
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
