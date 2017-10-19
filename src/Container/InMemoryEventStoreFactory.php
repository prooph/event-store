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

namespace Prooph\EventStore\Container;

use Prooph\Common\Event\ActionEventEmitter;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\TransactionalActionEventEmitterEventStore;
use Prooph\EventStore\TransactionalEventStore;

final class InMemoryEventStoreFactory extends AbstractInMemoryEventStoreFactory
{
    protected function getEventsForDefaultEmitter(): array
    {
        return TransactionalActionEventEmitterEventStore::ALL_EVENTS;
    }

    protected function createEventStoreInstance(): EventStore
    {
        return new InMemoryEventStore();
    }

    protected function createActionEventEmitterDecorator(
        EventStore $eventStore,
        ActionEventEmitter $actionEventEmitter
    ): ActionEventEmitterEventStore {
        assert($eventStore instanceof TransactionalEventStore);

        return new TransactionalActionEventEmitterEventStore($eventStore, $actionEventEmitter);
    }
}
