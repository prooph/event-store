<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStore\Repository;

use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamEvent;
use Prooph\EventStore\Stream\StreamId;

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
     * @return StreamId representation of the EventSourcedAggregateRoot
     */
    public function extractStreamId($anEventSourcedAggregateRoot);

    /**
     * @param \Prooph\EventStore\Stream\Stream $historyEvents
     * @return object reconstructed EventSourcedAggregateRoot
     */
    public function constructAggregateFromHistory(Stream $historyEvents);

    /**
     * @param object $anEventSourcedAggregateRoot
     * @return StreamEvent[]
     */
    public function extractPendingStreamEvents($anEventSourcedAggregateRoot);
}
