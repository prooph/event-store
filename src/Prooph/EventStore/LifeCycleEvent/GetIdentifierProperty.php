<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 17.04.14 - 23:03
 */

namespace Prooph\EventStore\LifeCycleEvent;

use Prooph\EventStore\EventSourcing\AggregateChangedEvent;
use Zend\EventManager\Event;

/**
 * Class GetIdentifierProperty
 *
 * @package Prooph\EventStore\LifeCycleEvent
 * @author Alexander Miertsch <contact@prooph.de>
 */
class GetIdentifierProperty extends Event
{
    const NAME = "GetIdentifierProperty";

    /**
     * @param mixed $aggregate
     */
    public function __construct($aggregate)
    {
        $this->setName(self::NAME);
        $this->setTarget($aggregate);
    }
}
 