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

use Prooph\EventStore\EventSourcing\AggregateChangedEvent;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Feature\FeatureInterface;
use Prooph\EventStore\PersistenceEvent\PostCommitEvent;

/**
 * Class EventLoggerFeature
 *
 * @package Prooph\EventStoreTest\Mock
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class EventLoggerFeature implements FeatureInterface
{
    /**
     * @var AggregateChangedEvent[]
     */
    protected $loggedEvents = array();

    /**
     * @param EventStore $eventStore
     * @return void
     */
    public function setUp(EventStore $eventStore)
    {
        $eventStore->getPersistenceEvents()->attach('commit.post', array($this, "onPostCommit"));
    }

    /**
     * @param PostCommitEvent $e
     */
    public function onPostCommit(PostCommitEvent $e)
    {
        $this->loggedEvents = $e->getPersistedEvents();
    }

    /**
     * @return \Prooph\EventStore\EventSourcing\AggregateChangedEvent[]
     */
    public function getLoggedEvents()
    {
        return $this->loggedEvents;
    }
}
 