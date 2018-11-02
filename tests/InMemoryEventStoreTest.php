<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore;

use Prooph\EventStore\InMemoryEventStore;

class InMemoryEventStoreTest extends AbstractEventStoreTest
{
    use EventStoreTestStreamTrait;
    use TransactionalEventStoreTestTrait;

    /**
     * @var InMemoryEventStore
     */
    protected $eventStore;

    protected function setUp(): void
    {
        $this->eventStore = new InMemoryEventStore();
    }
}
