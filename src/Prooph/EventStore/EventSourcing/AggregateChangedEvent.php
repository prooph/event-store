<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStore\EventSourcing;

use Codeliner\ArrayReader\ArrayReader;
use Codeliner\Domain\Shared\DomainEventInterface;
use Rhumsaa\Uuid\Uuid;
use ValueObjects\DateTime\DateTime;

/**
 * AggregateChangedEvent
 * 
 * @author Alexander Miertsch <contact@prooph.de>
 * @package Prooph\EventStore\EventSourcing
 */
class AggregateChangedEvent implements DomainEventInterface
{
    /**
     * @var Uuid
     */
    protected $uuid;

    /**
     * @var mixed
     */
    protected $aggregateId;

    /**
     * This property is injected via Reflection
     *
     * @var int
     */
    protected $version;

    /**
     * @var array
     */
    protected $payload;

    /**
     * @var DateTime
     */
    protected $occurredOn;

    /**
     * @param mixed $aggregateId
     * @param array $aPayload
     */
    public function __construct($aggregateId, array $aPayload)
    {
        $this->aggregateId = $aggregateId;
        $this->payload     = $aPayload;
        $this->uuid        = Uuid::uuid4();
        $this->occurredOn  = DateTime::now();
    }


    /**
     * @return Uuid
     */
    public function uuid()
    {
        return $this->uuid;
    }

    /**
     * @return int
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * @return DateTime
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

    /**
     * @return ArrayReader
     */
    public function toPayloadReader()
    {
        return new ArrayReader($this->payload());
    }

    /**
     * @return mixed
     */
    public function aggregateId()
    {
        return $this->aggregateId;
    }
}
