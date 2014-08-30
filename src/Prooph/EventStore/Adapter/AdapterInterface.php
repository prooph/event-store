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
     * @return void
     */
    public function remove(StreamName $aStreamName);

    /**
     * @param StreamName $aStreamName
     * @param array $metadata
     * @throws \Prooph\EventStore\Exception\StreamNotFoundException
     * @return void
     */
    public function removeEventsByMetadataFrom(StreamName $aStreamName, array $metadata);

    /**
     * @param StreamName $aStreamName
     * @return Stream|null
     */
    public function load(StreamName $aStreamName);

    /**
     * @param StreamName $aStreamName
     * @param array $metadata
     * @throws \Prooph\EventStore\Exception\StreamNotFoundException
     * @return StreamEvent[]
     */
    public function loadEventsByMetadataFrom(StreamName $aStreamName, array $metadata);
}
