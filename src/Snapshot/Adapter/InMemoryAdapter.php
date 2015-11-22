<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/09/15 - 07:20 PM
 */

namespace Prooph\EventStore\Snapshot\Adapter;

use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Snapshot\Snapshot;

/**
 * Class InMemoryAdapter
 * @package Prooph\EventStore\Snapshot\Adapter
 */
final class InMemoryAdapter implements Adapter
{
    /**
     * @var array
     */
    private $map = [];

    /**
     * Get the aggregate root if it exists otherwise null
     *
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @return null|object
     */
    public function get(AggregateType $aggregateType, $aggregateId)
    {
        if (! isset($this->map[$aggregateType->toString()][$aggregateId])) {
            return;
        }

        return $this->map[$aggregateType->toString()][$aggregateId];
    }

    /**
     * Save a snapshot
     *
     * @param Snapshot $snapshot
     * @return void
     */
    public function save(Snapshot $snapshot)
    {
        $this->map[$snapshot->aggregateType()->toString()][$snapshot->aggregateId()] = $snapshot;
    }
}
