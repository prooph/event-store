<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/7/15 - 11:01 PM
 */
namespace Prooph\EventStore\Aggregate;

/**
 * Class InMemoryIdentityMap
 *
 * This is the default IdentityMap used by the AggregateRepository if no other implementation was injected.
 * It just organizes aggregate roots in internal array maps and keep them in memory.
 *
 * @package Prooph\EventStore\Aggregate
 */
final class InMemoryIdentityMap implements IdentityMap
{
    /**
     * @var array
     */
    private $persistentMap = [];

    /**
     * @var array
     */
    private $dirtyMap = [];

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
    public function add(AggregateType $aggregateType, $aggregateId, $aggregateRoot)
    {
        $this->dirtyMap[$aggregateType->toString()][$aggregateId]
            = $this->persistentMap[$aggregateType->toString()][$aggregateId]
            = $aggregateRoot;
    }

    /**
     * Returns true if it has an aggregate root in the identity map
     *
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @return bool
     */
    public function has(AggregateType $aggregateType, $aggregateId)
    {
        return isset($this->persistentMap[$aggregateType->toString()][$aggregateId]);
    }

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
    public function get(AggregateType $aggregateType, $aggregateId)
    {
        if (! $this->has($aggregateType, $aggregateId)) {
            return;
        }

        return $this->dirtyMap[$aggregateType->toString()][$aggregateId]
            = $this->persistentMap[$aggregateType->toString()][$aggregateId];
    }

    /**
     * Returns all aggregate roots of given type which are currently flagged as dirty
     * The resulting array MUST be indexed by aggregate id.
     *
     * @param AggregateType $aggregateType
     * @return array indexed by aggregate id
     */
    public function getAllDirtyAggregateRoots(AggregateType $aggregateType)
    {
        return isset($this->dirtyMap[$aggregateType->toString()])
            ? $this->dirtyMap[$aggregateType->toString()] : [];
    }

    /**
     * This method is called each time the repository has applied pending events to all dirty aggregate roots
     * of the given type. So the identity map can remove the dirty flag for all effected aggregate roots.
     *
     * @param AggregateType $aggregateType
     * @return void
     */
    public function cleanUp(AggregateType $aggregateType)
    {
        unset($this->dirtyMap[$aggregateType->toString()]);
    }
}
