<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Mock;

use Prooph\Common\Messaging\DomainEvent;
use Prooph\Common\Messaging\PayloadConstructable;
use Prooph\Common\Messaging\PayloadTrait;

/**
 * Class DomainEvent
 *
 * @package ProophTest\EventStore\Mock
 * @author Alexander Miertsch <contact@prooph.de>
 */
class TestDomainEvent extends DomainEvent implements PayloadConstructable
{
    use PayloadTrait;

    public static function with(array $payload, int $version) : TestDomainEvent
    {
        $event = new static($payload);

        return $event->withVersion($version);
    }

    public static function withPayloadAndSpecifiedCreatedAt(array $payload, int $version, \DateTimeImmutable $createdAt) : TestDomainEvent
    {
        $event = new static($payload);
        $event->createdAt = $createdAt;

        return $event->withVersion($version);
    }
}
