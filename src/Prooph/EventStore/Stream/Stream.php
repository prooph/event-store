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
    protected $streamId;

    /**
     * @var StreamEvent[]
     */
    protected $streamEvents;

    /**
     * @param StreamName $streamId
     * @param StreamEvent[] $streamEvents
     */
    public function __construct(StreamName $streamId, array $streamEvents)
    {
        \Assert\that($streamEvents)->all()->isInstanceOf('Prooph\EventStore\Stream\StreamEvent');

        $this->streamId = $streamId;

        $this->streamEvents = $streamEvents;
    }

    /**
     * @return StreamName
     */
    public function streamId()
    {
        return $this->streamId;
    }

    /**
     * @return StreamEvent[]
     */
    public function streamEvents()
    {
        return $this->streamEvents;
    }
}
 