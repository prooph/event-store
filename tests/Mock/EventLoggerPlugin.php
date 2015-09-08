<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 04/21/14 - 00:13 AM
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
    protected $loggedStreamEvents = [];

    /**
     * @param EventStore $eventStore
     * @return void
     */
    public function setUp(EventStore $eventStore)
    {
        $eventStore->getActionEventEmitter()->attachListener('commit.post', [$this, "onPostCommit"]);
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
