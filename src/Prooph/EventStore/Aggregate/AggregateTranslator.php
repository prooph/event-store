<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 31.08.14 - 00:27
 */

namespace Prooph\EventStore\Aggregate;

use Prooph\Common\Messaging\DomainEvent;

/**
 * Interface AggregateTranslator
 *
 * @package Prooph\EventStore\Aggregate
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface AggregateTranslator
{
    /**
     * @param object $anEventSourcedAggregateRoot
     * @return string
     */
    public function extractAggregateId($anEventSourcedAggregateRoot);

    /**
     * @param AggregateType $aggregateType
     * @param DomainEvent[] $historyEvents
     * @return object reconstructed EventSourcedAggregateRoot
     */
    public function reconstituteAggregateFromHistory(AggregateType $aggregateType, array $historyEvents);

    /**
     * @param object $anEventSourcedAggregateRoot
     * @return DomainEvent[]
     */
    public function extractPendingStreamEvents($anEventSourcedAggregateRoot);
}
