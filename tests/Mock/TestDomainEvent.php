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

use Prooph\Common\Messaging\DomainEvent;
use Prooph\Common\Messaging\PayloadConstructable;
use Prooph\Common\Messaging\PayloadTrait;

/**
 * Class DomainEvent
 *
 * @package Prooph\EventStoreTest\Mock
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
}
