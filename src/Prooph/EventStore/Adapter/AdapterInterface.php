<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStore\Adapter;

use Prooph\EventStore\Stream\AggregateType;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamId;

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
     * @param AggregateType $aggregateType
     * @param StreamId $streamId
     * @param null|int $version
     *
     * @return Stream[]
     */
    public function loadStream(AggregateType $aggregateType, StreamId $streamId, $version = null);
    
    /**
     * Add new stream to the source stream
     * 
     * @param Stream $stream
     * 
     * @return void
     */
    public function addToExistingStream(Stream $stream);

    /**
     * @param AggregateType $aggregateType
     * @param StreamId $streamId
     */
    public function removeStream(AggregateType $aggregateType, StreamId $streamId);
}
