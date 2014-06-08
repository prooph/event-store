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
 * Class StreamEvent
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class StreamEvent
{
    /**
     * @var EventId
     */
    protected $eventId;

    /**
     * @var EventName
     */
    protected $eventName;

    /**
     * @var int
     */
    protected $version;

    /**
     * @var array
     */
    protected $payload;

    /**
     * @var \DateTime
     */
    protected $occurredOn;

    /**
     * @param EventId $eventId
     * @param EventName $eventName
     * @param array $payload
     * @param integer $version
     * @param \DateTime $occurredOn
     */
    public function __construct(EventId $eventId, EventName $eventName, array $payload, $version, \DateTime $occurredOn)
    {
        \Assert\that($version)->integer();

        $this->eventId = $eventId;
        $this->eventName = $eventName;
        $this->payload = $payload;
        $this->version = $version;
        $this->occurredOn = $occurredOn;
    }


    /**
     * @return EventId
     */
    public function eventId()
    {
        return $this->eventId;
    }

    /**
     * @return EventName
     */
    public function eventName()
    {
        return $this->eventName;
    }

    /**
     * @return int
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * @return \DateTime
     */
    public function occurredOn()
    {
        return $this->occurredOn;
    }

    /**
     * @return array
     */
    public function payload()
    {
        return $this->payload;
    }
}
 