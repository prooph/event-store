<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 08/31/14 - 8:02 PM
 */

namespace ProophTest\EventStore\Mock;

use Prooph\Common\Messaging\DomainEvent;
use Prooph\Common\Messaging\PayloadConstructable;
use Prooph\Common\Messaging\PayloadTrait;

/**
 * Class DomainEvent
 *
 * @package ProophTest\EventStore\Mock
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class TestDomainEvent extends DomainEvent implements PayloadConstructable
{
    use PayloadTrait;

    /**
     * @param array $payload
     * @param int $version
     * @return TestDomainEvent
     */
    public static function with(array $payload, $version)
    {
        $event = new static($payload);

        return $event->withVersion($version);
    }

    /**
     * @param array $payload
     * @param int $version
     * @param \DateTimeImmutable $createdAt
     * @return TestDomainEvent
     */
    public static function withPayloadAndSpecifiedCreatedAt(array $payload, $version, \DateTimeImmutable $createdAt)
    {
        $event = new static($payload);
        $event->createdAt = $createdAt;

        return $event->withVersion($version);
    }
}
