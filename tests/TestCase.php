<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\TransactionalActionEventEmitterEventStore;

abstract class TestCase extends PHPUnitTestCase
{
    /**
     * @var InMemoryEventStore
     */
    protected $eventStore;

    protected function setUp(): void
    {
        $eventEmitter = new ProophActionEventEmitter([
            TransactionalActionEventEmitterEventStore::EVENT_APPEND_TO,
            TransactionalActionEventEmitterEventStore::EVENT_CREATE,
            TransactionalActionEventEmitterEventStore::EVENT_LOAD,
            TransactionalActionEventEmitterEventStore::EVENT_LOAD_REVERSE,
            TransactionalActionEventEmitterEventStore::EVENT_DELETE,
            TransactionalActionEventEmitterEventStore::EVENT_HAS_STREAM,
            TransactionalActionEventEmitterEventStore::EVENT_FETCH_STREAM_METADATA,
            TransactionalActionEventEmitterEventStore::EVENT_BEGIN_TRANSACTION,
            TransactionalActionEventEmitterEventStore::EVENT_COMMIT,
            TransactionalActionEventEmitterEventStore::EVENT_ROLLBACK,
        ]);

        $this->eventStore = new InMemoryEventStore($eventEmitter);
    }
}
