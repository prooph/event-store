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

use Prooph\EventStore\Aggregate\AggregateType;

/**
 * Interface StreamStrategyInterface
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface StreamStrategyInterface 
{
    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @param StreamEvent[] $streamEvents
     * @return void
     */
    public function register(AggregateType $aggregateType, $aggregateId, array $streamEvents);

    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @param StreamEvent[] $streamEvents
     * @return void
     */
    public function appendEvents(AggregateType $aggregateType, $aggregateId, array $streamEvents);

    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @return void
     */
    public function remove(AggregateType $aggregateType, $aggregateId);

    /**
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @return StreamEvent[]
     */
    public function read(AggregateType $aggregateType, $aggregateId);
}
 