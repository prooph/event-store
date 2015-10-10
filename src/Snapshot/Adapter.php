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

namespace Prooph\EventStore\Snapshot;

use Prooph\EventStore\Aggregate\AggregateType;

/**
 * Interface Adapter
 * @package Prooph\EventStore\Snapshot
 */
interface Adapter
{
    /**
     * Get the aggregate root if it exists otherwise null
     *
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @return Snapshot
     */
    public function get(AggregateType $aggregateType, $aggregateId);

    /**
     * Add a snapshot
     *
     * @param Snapshot $snapshot
     * @return void
     */
    public function add(Snapshot $snapshot);
}
