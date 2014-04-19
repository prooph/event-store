<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStore\Repository;

use Prooph\EventStore\EventSourcing\EventSourcedAggregateRoot;
use Prooph\EventStore\EventStore;

/**
 * RepositoryInterface
 * 
 * @author Alexander Miertsch <contact@prooph.de>
 * @package Prooph\EventStore\Repository
 */
interface RepositoryInterface
{
    /**
     * Construct
     * 
     * @param EventStore $eventStore
     * @param string     $aggregateFQCN
     */
    public function __construct(EventStore $eventStore, $aggregateFQCN);

    /**
     * Add an EventSourcedAggregateRoot
     *
     * @param EventSourcedAggregateRoot $anEventSourcedAggregateRoot
     * @return void
     */
    public function add(EventSourcedAggregateRoot $anEventSourcedAggregateRoot);
    
    /**
     * Get an EventSourcedAggregateRoot by it's id
     * 
     * @param mixed $anAggregateId
     * 
     * @return EventSourcedAggregateRoot|null
     */
    public function get($anAggregateId);

    /**
     * Remove an EventSourcedAggregateRoot
     *
     * @param \Prooph\EventStore\EventSourcing\EventSourcedAggregateRoot $anEventSourcedAggregateRoot
     * @return void
     */
    public function remove(EventSourcedAggregateRoot $anEventSourcedAggregateRoot);
}
