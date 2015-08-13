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

use Prooph\Common\Messaging\Message;

/**
 * Interface AggregateTranslator
 *
 * @package Prooph\EventStore\Aggregate
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface AggregateTranslator
{
    /**
     * @param object $eventSourcedAggregateRoot
     * @return string
     */
    public function extractAggregateId($eventSourcedAggregateRoot);

    /**
     * @param AggregateType $aggregateType
     * @param Message[] $historyEvents
     * @return object reconstructed EventSourcedAggregateRoot
     */
    public function reconstituteAggregateFromHistory(AggregateType $aggregateType, array $historyEvents);

    /**
     * @param object $eventSourcedAggregateRoot
     * @return Message[]
     */
    public function extractPendingStreamEvents($eventSourcedAggregateRoot);
}
 