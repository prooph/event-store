<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\EventStore\Mock;

use Prooph\EventStore\Aggregate\AggregateRepository;
use Prooph\EventStore\Aggregate\AggregateTranslator;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Snapshot\SnapshotStore;
use Prooph\EventStore\Stream\StreamName;

final class RepositoryMock extends AggregateRepository
{
    /**
     * @return EventStore
     */
    public function accessEventStore()
    {
        return $this->eventStore;
    }
    /**
     * @return AggregateType
     */
    public function accessAggregateType()
    {
        return $this->aggregateType;
    }

    /**
     * @return AggregateTranslator
     */
    public function accessAggregateTranslator()
    {
        return $this->aggregateTranslator;
    }

    /**
     * @param string|null $aggregateId
     * @return StreamName
     */
    public function accessDeterminedStreamName($aggregateId = null)
    {
        return $this->determineStreamName($aggregateId);
    }

    /**
     * @return bool
     */
    public function accessOneStreamPerAggregateFlag()
    {
        return $this->oneStreamPerAggregate;
    }

    /**
     * @return SnapshotStore
     */
    public function accessSnapshotStore()
    {
        return $this->snapshotStore;
    }
}
