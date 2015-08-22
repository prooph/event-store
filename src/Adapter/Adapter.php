<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStore\Adapter;

use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;

/**
 * Interface of an EventStore Adapter
 *
 * @author Alexander Miertsch <contact@prooph.de>
 * @package Prooph\EventStore\Adapter
 */
interface Adapter
{
    /**
     * @param Stream $stream
     * @return void
     */
    public function create(Stream $stream);

    /**
     * @param StreamName $streamName
     * @param Message[] $domainEvents
     * @throws \Prooph\EventStore\Exception\StreamNotFoundException If stream does not exist
     * @return void
     */
    public function appendTo(StreamName $streamName, array $domainEvents);

    /**
     * @param StreamName $streamName
     * @param null|int $minVersion Minimum version an event should have
     * @return Stream|null
     */
    public function load(StreamName $streamName, $minVersion = null);

    /**
     * @param StreamName $streamName
     * @param array $metadata If empty array is provided, then all events should be returned
     * @param null|int $minVersion Minimum version an event should have
     * @return Message[]
     */
    public function loadEventsByMetadataFrom(StreamName $streamName, array $metadata, $minVersion = null);
}
