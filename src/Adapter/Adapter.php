<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\EventStore\Adapter;

use DateTimeInterface;
use Iterator;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use Prooph\EventStore\Exception\StreamNotFoundException;

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
     * @param Iterator $domainEvents
     * @throws StreamNotFoundException If stream does not exist
     * @return void
     */
    public function appendTo(StreamName $streamName, Iterator $domainEvents);

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
     * @return Iterator
     */
    public function loadEvents(StreamName $streamName, array $metadata = [], $minVersion = null);

    /**
     * @param StreamName $streamName
     * @param DateTimeInterface|null $since
     * @param array $metadata
     * @return Iterator
     */
    public function replay(StreamName $streamName, DateTimeInterface $since = null, array $metadata = []);
}
