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

use Prooph\Common\Event\ZF2\Zf2ActionEvent;
use Prooph\Common\Messaging\DomainEvent;
use Prooph\EventStore\EventStore;

/**
 * Class PostCommitEvent
 *
 * @package Prooph\EventStore\PersistenceEvent
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class PostCommitEvent extends Zf2ActionEvent
{
    /**
     * @return EventStore
     */
    public function getEventStore()
    {
        return $this->getTarget();
    }

    /**
     * @return DomainEvent[]
     */
    public function getRecordedEvents()
    {
        return $this->getParam('recordedEvents', []);
    }
}
