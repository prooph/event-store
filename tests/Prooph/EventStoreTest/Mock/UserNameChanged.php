<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 18.04.14 - 00:08
 */

namespace Prooph\EventStoreTest\Mock;

use Prooph\EventSourcing\DomainEvent\AggregateChangedEvent;

/**
 * Class UserNameChanged
 *
 * @package Prooph\EventStoreTest\Mock
 * @author Alexander Miertsch <contact@prooph.de>
 */
class UserNameChanged extends AggregateChangedEvent
{
    /**
     * @return string
     */
    public function newUsername()
    {
        return $this->toPayloadReader()->stringValue('username');
    }
}
 