<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStore\Adapter;

use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamEvent;
use Prooph\EventStore\Stream\StreamName;

/**
 * Interface of an EventStore Adapter
 * 
 * @author Alexander Miertsch <contact@prooph.de>
 * @package Prooph\EventStore\Adapter
 */
interface AdapterInterface
{
    /**
     * @param Stream $aStream
     * @return void
     */
    public function create(Stream $aStream);

    /**
     * @param StreamName $aStreamName
     * @param array $streamEvents
     * @throws \Prooph\EventStore\Exception\StreamNotFoundException If stream does not exist
     * @return void
     */
    public function appendTo(StreamName $aStreamName, array $streamEvents);

    /**
     * @param StreamName $aStreamName
     * @param null|int $minVersion Minimum version an event should have
     * @return Stream|null
     */
    public function load(StreamName $aStreamName, $minVersion = null);

    /**
     * @param StreamName $aStreamName
     * @param array $metadata If empty array is provided, then all events should be returned
     * @param null|int $minVersion Minimum version an event should have
     * @return StreamEvent[]
     */
    public function loadEventsByMetadataFrom(StreamName $aStreamName, array $metadata, $minVersion = null);
}
