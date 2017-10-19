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

namespace ProophTest\EventStore\Container;

use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\Container\AbstractInMemoryEventStoreFactory;
use Prooph\EventStore\Container\InMemoryEventStoreFactory;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\InMemoryEventStore;

class InMemoryEventStoreFactoryTest extends AbstractInMemoryEventStoreFactoryTest
{
    protected function getEventStoreClassName(): string
    {
        return InMemoryEventStore::class;
    }

    protected function getActionEventEmitterDecoratorClassName(): string
    {
        return ActionEventEmitterEventStore::class;
    }

    protected function createEventStoreFactoryInstance(): AbstractInMemoryEventStoreFactory
    {
        return new InMemoryEventStoreFactory();
    }

    protected function createFromFactoryViaCallStatic(string $type, $container): EventStore
    {
        return InMemoryEventStoreFactory::$type($container);
    }
}
