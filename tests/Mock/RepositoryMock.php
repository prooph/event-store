<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/21/15 - 8:10 PM
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
