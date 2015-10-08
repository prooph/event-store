<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/7/15 - 10:09 PM
 */

namespace Prooph\EventStore\Aggregate;

/**
 * Interface IdentityMap
 *
 * The identity map is an alternative storage for aggregate roots.
 * It is used by a repository to "cache" aggregate roots for later use without the need
 * to load and replay history events. The identity map should behave like an in-memory storage.
 *
 * The identity map MUST be able to flag aggregate roots as dirty as long as the cleanUp method is not called.
 * See method doc blocks for more details!
 *
 * @package Prooph\EventStore\Aggregate
 */
interface IdentityMap
{
    /**
     * Add aggregate root to the identity map
     * The aggregate root MUST be flagged as dirty
     * so that {@method getAllDirtyAggregateRoots} returns the AR
     * as long as {@method cleanUp} is not called
     *
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @param object $aggregateRoot
     * @return void
     */
    public function add(AggregateType $aggregateType, $aggregateId, $aggregateRoot);

    /**
     * Returns true if it has an aggregate root in the identity map
     *
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @return bool
     */
    public function has(AggregateType $aggregateType, $aggregateId);

    /**
     * Get the aggregate root if it exists otherwise null
     * The returned aggregate root MUST be flagged as dirty internally
     * so that {@method getAllDirtyAggregateRoots} returns the AR
     * as long as {@method cleanUp} is not called
     *
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @return null|object the cached aggregate root
     */
    public function get(AggregateType $aggregateType, $aggregateId);

    /**
     * Returns all aggregate roots of given type which are currently flagged as dirty
     * The resulting array MUST be indexed by aggregate id.
     *
     * @param AggregateType $aggregateType
     * @return array indexed by aggregate id
     */
    public function getAllDirtyAggregateRoots(AggregateType $aggregateType);

    /**
     * This method is called each time the repository has applied pending events to all dirty aggregate roots
     * of the given type. So the identity map can remove the dirty flag for all effected aggregate roots.
     *
     * @param AggregateType $aggregateType
     * @return void
     */
    public function cleanUp(AggregateType $aggregateType);
}
