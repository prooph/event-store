<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 17.04.14 - 20:56
 */

namespace Prooph\EventStore\Adapter\Builder;

use Prooph\EventStore\EventSourcing\AggregateChangedEvent;
use Rhumsaa\Uuid\Uuid;
use ValueObjects\DateTime\DateTime;

/**
 * Class EventBuilder
 *
 * @package Prooph\EventStore\Adapter\Builder
 * @author Alexander Miertsch <contact@prooph.de>
 */
class EventBuilder 
{
    /**
     * @param string   $eventFQCN
     * @param Uuid     $uuid
     * @param mixed    $aggregateId
     * @param DateTime $occurredOn
     * @param int      $version
     * @param array    $payload
     * @return AggregateChangedEvent
     */
    public static function reconstructEvent($eventFQCN, Uuid $uuid, $aggregateId, DateTime $occurredOn, $version, array $payload)
    {
        $eventRef = new \ReflectionClass($eventFQCN);

        $event = $eventRef->newInstanceWithoutConstructor();

        $uuidProp = $eventRef->getProperty('uuid');

        $uuidProp->setAccessible(true);

        $uuidProp->setValue($event, $uuid);

        $aggregateIdProp = $eventRef->getProperty('aggregateId');

        $aggregateIdProp->setAccessible(true);

        $aggregateIdProp->setValue($event, $aggregateId);

        $occurredOnProp = $eventRef->getProperty('occurredOn');

        $occurredOnProp->setAccessible(true);

        $occurredOnProp->setValue($event, $occurredOn);

        $versionProp = $eventRef->getProperty('version');

        $versionProp->setAccessible(true);

        $versionProp->setValue($event, $version);

        $payloadProp = $eventRef->getProperty('payload');

        $payloadProp->setAccessible(true);

        $payloadProp->setValue($event, $payload);

        return $event;
    }
}
 