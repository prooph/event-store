<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore;

use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\InMemoryEventStore;

abstract class ActionEventEmitterEventStoreTestCase extends EventStoreTestCase
{
    /**
     * @var ActionEventEmitterEventStore
     */
    protected $eventStore;

    protected function setUp(): void
    {
        $eventEmitter = new ProophActionEventEmitter([
            ActionEventEmitterEventStore::EVENT_APPEND_TO,
            ActionEventEmitterEventStore::EVENT_CREATE,
            ActionEventEmitterEventStore::EVENT_LOAD,
            ActionEventEmitterEventStore::EVENT_LOAD_REVERSE,
            ActionEventEmitterEventStore::EVENT_DELETE,
            ActionEventEmitterEventStore::EVENT_HAS_STREAM,
            ActionEventEmitterEventStore::EVENT_FETCH_STREAM_METADATA,
            ActionEventEmitterEventStore::EVENT_UPDATE_STREAM_METADATA,
            ActionEventEmitterEventStore::EVENT_DELETE_PROJECTION,
            ActionEventEmitterEventStore::EVENT_RESET_PROJECTION,
            ActionEventEmitterEventStore::EVENT_STOP_PROJECTION,
        ]);

        $this->eventStore = new ActionEventEmitterEventStore(new InMemoryEventStore(), $eventEmitter);
    }
}
