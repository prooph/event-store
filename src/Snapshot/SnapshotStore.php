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
use Prooph\EventStore\Snapshot\Adapter\Adapter;

/**
 * Class SnapshotStore
 * @package Prooph\EventStore\Snapshot
 */
class SnapshotStore
{
    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * @param Adapter $adapter
     */
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @return Snapshot|null
     */
    public function get(AggregateType $aggregateType, $aggregateId)
    {
        return $this->adapter->get($aggregateType, $aggregateId);
    }

    /**
     * Add aggregate root
     *
     * @param Snapshot $snapshot
     * @return void
     */
    public function add(Snapshot $snapshot)
    {
        $this->adapter->add($snapshot);
    }
}
