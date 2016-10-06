<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function get(AggregateType $aggregateType, $aggregateId): ?Snapshot
    {
        return $this->adapter->get($aggregateType, $aggregateId);
    }

    public function save(Snapshot $snapshot): void
    {
        $this->adapter->save($snapshot);
    }
}
