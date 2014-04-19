<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStore\Adapter;

use Prooph\EventStore\EventSourcing\AggregateChangedEvent;

/**
 * Interface of an EventStore Adapter
 * 
 * @author Alexander Miertsch <contact@prooph.de>
 * @package Prooph\EventStore\Adapter
 */
interface AdapterInterface
{
    /**
     * Load EventStream of an EventSourcedAggregateRoot from given version on
     *
     * Pass null as version to get the complete stream
     *
     * @param string $aggregateFQCN
     * @param string $aggregateId
     * @param int    $version
     *
     * @return AggregateChangedEvent[]
     */
    public function loadStream($aggregateFQCN, $aggregateId, $version = null);
    
    /**
     * Add events to the source stream
     * 
     * @param string                  $aggregateFQCN
     * @param string                  $aggregateId
     * @param AggregateChangedEvent[] $events
     * 
     * @return void
     */
    public function addToStream($aggregateFQCN, $aggregateId, $events);
}
