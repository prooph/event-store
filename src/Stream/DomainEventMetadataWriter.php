<?php
/**
 * This file is part of VehicleManagement
 * Date: 5/1/15 - 7:10 PM
 * (c) Sixt GmbH & Co. Autovermietung KG
 */
namespace Prooph\EventStore\Stream;

use Prooph\Common\Messaging\DomainEvent;

/**
 * Class DomainEventMetadataWriter
 *
 * The DomainEventMetadataWriter uses the decorator pattern to access the protected metadata property of a domain event
 * and manipulate it.
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 * @internal is used internally to add metadata to a DomainEvent
 */
final class DomainEventMetadataWriter extends DomainEvent
{
    /**
     * @param DomainEvent $domainEvent
     * @param string $key
     * @param mixed $value
     */
    public static function setMetadataKey(DomainEvent $domainEvent, $key, $value)
    {
        $domainEvent->metadata[$key] = $value;
    }
} 