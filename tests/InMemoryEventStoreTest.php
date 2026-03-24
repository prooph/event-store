<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2026 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2026 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore;

use Prooph\EventStore\InMemoryEventStore;

class InMemoryEventStoreTest extends AbstractEventStoreTestCase
{
    use EventStoreTestStreamTrait;
    use TransactionalEventStoreTestTrait;

    protected function setUp(): void
    {
        $this->eventStore = new InMemoryEventStore();
    }
}
