<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 06.06.14 - 22:35
 */

namespace Prooph\EventStore\Stream;

/**
 * Class Stream
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class Stream 
{
    /**
     * @var StreamName
     */
    protected $streamName;

    /**
     * @var StreamEvent[]
     */
    protected $streamEvents;

    /**
     * @param StreamName $streamName
     * @param StreamEvent[] $streamEvents
     */
    public function __construct(StreamName $streamName, array $streamEvents)
    {
        \Assert\that($streamEvents)->all()->isInstanceOf('Prooph\EventStore\Stream\StreamEvent');

        $this->streamName = $streamName;

        $this->streamEvents = $streamEvents;
    }

    /**
     * @return StreamName
     */
    public function streamName()
    {
        return $this->streamName;
    }

    /**
     * @return StreamEvent[]
     */
    public function streamEvents()
    {
        return $this->streamEvents;
    }
}
 