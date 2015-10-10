<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 08/14/31 - 00:37
 */

namespace Prooph\EventStore\Stream;

use Iterator;
use Prooph\EventStore\Aggregate\AggregateType;

/**
 * Interface StreamStrategy
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface StreamStrategy
{
    /**
     * @param AggregateType $repositoryAggregateType
     * @param string $aggregateId
     * @param Iterator $streamEvents
     * @param object $aggregateRoot
     * @return void
     */
    public function addEventsForNewAggregateRoot(
        AggregateType $repositoryAggregateType,
        $aggregateId,
        Iterator $streamEvents,
        $aggregateRoot
    );

    /**
     * @param AggregateType $repositoryAggregateType
     * @param string $aggregateId
     * @param Iterator $streamEvents
     * @param object $aggregateRoots
     * @return void
     */
    public function appendEvents(
        AggregateType $repositoryAggregateType,
        $aggregateId,
        Iterator $streamEvents,
        $aggregateRoots
    );

    /**
     * @param AggregateType $repositoryAggregateType
     * @param string $aggregateId
     * @param null|int $minVersion
     * @return Iterator
     */
    public function read(AggregateType $repositoryAggregateType, $aggregateId, $minVersion = null);

    /**
     * A stream strategy can provide another AggregateType that should be used to reconstitute the aggregate root.
     * This can be useful when working with aggregate hierarchies where all sub aggregate roots are managed
     * within the same stream as the super aggregate root.
     *
     * @param AggregateType $repositoryAggregateType
     * @param Iterator $streamEvents
     * @return AggregateType
     */
    public function getAggregateRootType(AggregateType $repositoryAggregateType, Iterator $streamEvents);
}
