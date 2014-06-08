<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 18.04.14 - 00:29
 */

namespace Prooph\EventStoreTest\Mock;

use Prooph\EventSourcing\DomainEvent\AggregateChangedEvent;

/**
 * Class UserCreated
 *
 * @package Prooph\EventStoreTest\Mock
 * @author Alexander Miertsch <contact@prooph.de>
 */
class UserCreated extends AggregateChangedEvent
{
    /**
     * @return string
     */
    public function userId()
    {
        return $this->toPayloadReader()->stringValue('id');
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->toPayloadReader()->stringValue('name');
    }
}
 