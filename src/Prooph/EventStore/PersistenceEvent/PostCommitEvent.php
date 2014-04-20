<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 20.04.14 - 20:53
 */

namespace Prooph\EventStore\PersistenceEvent;

use Prooph\EventStore\EventSourcing\AggregateChangedEvent;
use Prooph\EventStore\EventStore;
use Zend\EventManager\Event;

/**
 * Class PostCommitEvent
 *
 * @package Prooph\EventStore\PersistenceEvent
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class PostCommitEvent extends Event
{
    /**
     * @return EventStore
     */
    public function getEventStore()
    {
        return $this->getTarget();
    }

    /**
     * @return AggregateChangedEvent[]
     */
    public function getPersistedEvents()
    {
        return $this->getParam('persistedEvents', array());
    }
}
 