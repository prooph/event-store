<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 31.08.14 - 00:37
 */

namespace Prooph\EventStore\Stream;

use Prooph\Common\Messaging\Message;
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
     * @param Message[] $streamEvents
     * @param object $aggregateRoot
     * @return void
     */
    public function addEventsForNewAggregateRoot(AggregateType $repositoryAggregateType, $aggregateId, array $streamEvents, $aggregateRoot);

    /**
     * @param AggregateType $repositoryAggregateType
     * @param string $aggregateId
     * @param Message[] $streamEvents
     * @param object $aggregateRoots
     * @return void
     */
    public function appendEvents(AggregateType $repositoryAggregateType, $aggregateId, array $streamEvents, $aggregateRoots);

    /**
     * @param AggregateType $repositoryAggregateType
     * @param string $aggregateId
     * @param null|int $minVersion
     * @return Message[]
     */
    public function read(AggregateType $repositoryAggregateType, $aggregateId, $minVersion = null);

    /**
     * A stream strategy can provide another AggregateType that should be used to reconstitute the aggregate root.
     * This can be useful when working with aggregate hierarchies where all sub aggregate roots are managed
     * within the same stream as the super aggregate root.
     *
     * @param AggregateType $repositoryAggregateType
     * @param Message[] $streamEvents
     * @return AggregateType
     */
    public function getAggregateRootType(AggregateType $repositoryAggregateType, array &$streamEvents);
}
