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

namespace ProophTest\EventStore\Mock;

use Prooph\EventStore\Aggregate\AggregateRepository;
use Prooph\EventStore\Aggregate\AggregateTranslator;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Snapshot\SnapshotStore;
use Prooph\EventStore\Stream\StreamName;

final class RepositoryMock extends AggregateRepository
{
    public function accessEventStore(): EventStore
    {
        return $this->eventStore;
    }

    public function accessAggregateType(): AggregateType
    {
        return $this->aggregateType;
    }

    public function accessAggregateTranslator(): AggregateTranslator
    {
        return $this->aggregateTranslator;
    }

    public function accessDeterminedStreamName(?string $aggregateId = null): StreamName
    {
        return $this->determineStreamName($aggregateId);
    }

    public function accessOneStreamPerAggregateFlag(): bool
    {
        return $this->oneStreamPerAggregate;
    }

    public function accessSnapshotStore(): SnapshotStore
    {
        return $this->snapshotStore;
    }
}
