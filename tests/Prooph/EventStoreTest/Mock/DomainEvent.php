<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 31.08.14 - 20:02
 */

namespace Prooph\EventStoreTest\Mock;

use Prooph\EventStore\Stream\EventId;
use Prooph\EventStore\Stream\EventName;
use Prooph\EventStore\Stream\StreamEvent;
use Rhumsaa\Uuid\Uuid;

/**
 * Class DomainEvent
 *
 * @package Prooph\EventStoreTest\Mock
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class DomainEvent extends StreamEvent
{
    /**
     * @param array $payload
     * @param int $version
     * @return DomainEvent
     */
    public static function with(array $payload, $version)
    {
        return new static(new EventId(Uuid::uuid4()->toString()), new EventName(get_called_class()), $payload, $version, new \DateTime());
    }
}
 