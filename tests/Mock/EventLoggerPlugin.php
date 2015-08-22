<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 21.04.14 - 00:13
 */

namespace Prooph\EventStoreTest\Mock;

use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Messaging\DomainEvent;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Plugin\Plugin;

/**
 * Class EventLoggerFeature
 *
 * @package Prooph\EventStoreTest\Mock
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class EventLoggerPlugin implements Plugin
{
    /**
     * @var DomainEvent[]
     */
    protected $loggedStreamEvents = array();

    /**
     * @param EventStore $eventStore
     * @return void
     */
    public function setUp(EventStore $eventStore)
    {
        $eventStore->getActionEventEmitter()->attachListener('commit.post', array($this, "onPostCommit"));
    }

    /**
     * @param ActionEvent $e
     */
    public function onPostCommit(ActionEvent $e)
    {
        $this->loggedStreamEvents = $e->getParam('recordedEvents', []);
    }

    /**
     * @return DomainEvent[]
     */
    public function getLoggedStreamEvents()
    {
        return $this->loggedStreamEvents;
    }
}
 