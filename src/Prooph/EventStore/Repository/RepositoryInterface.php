<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStore\Repository;

use Prooph\EventStore\EventSourcing\AggregateChangedEvent;

/**
 * RepositoryInterface
 * 
 * @author Alexander Miertsch <contact@prooph.de>
 * @package Prooph\EventStore\Repository
 */
interface RepositoryInterface
{
    /**
     * @param object $anEventSourcedAggregateRoot
     * @return string representation of the EventSourcedAggregateRoot
     */
    public function extractAggregateIdAsString($anEventSourcedAggregateRoot);

    /**
     * @param string $anAggregateType
     * @param string $anAggregateId
     * @param AggregateChangedEvent[] $historyEvents
     * @return object reconstructed EventSourcedAggregateRoot
     */
    public function constructAggregateFromHistory($anAggregateType, $anAggregateId, array $historyEvents);

    /**
     * @param object $anEventSourcedAggregateRoot
     * @return AggregateChangedEvent[]
     */
    public function extractPendingEvents($anEventSourcedAggregateRoot);
}
