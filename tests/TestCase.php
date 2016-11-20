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

use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\CanControlTransactionActionEventEmitterAwareEventStore;
use Prooph\EventStore\InMemoryEventStore;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InMemoryEventStore
     */
    protected $eventStore;

    protected function setUp(): void
    {
        $eventEmitter = new ProophActionEventEmitter([
            CanControlTransactionActionEventEmitterAwareEventStore::EVENT_APPEND_TO,
            CanControlTransactionActionEventEmitterAwareEventStore::EVENT_CREATE,
            CanControlTransactionActionEventEmitterAwareEventStore::EVENT_LOAD,
            CanControlTransactionActionEventEmitterAwareEventStore::EVENT_LOAD_REVERSE,
            CanControlTransactionActionEventEmitterAwareEventStore::EVENT_DELETE,
            CanControlTransactionActionEventEmitterAwareEventStore::EVENT_HAS_STREAM,
            CanControlTransactionActionEventEmitterAwareEventStore::EVENT_FETCH_STREAM_METADATA,
            CanControlTransactionActionEventEmitterAwareEventStore::EVENT_BEGIN_TRANSACTION,
            CanControlTransactionActionEventEmitterAwareEventStore::EVENT_COMMIT,
            CanControlTransactionActionEventEmitterAwareEventStore::EVENT_ROLLBACK,
        ]);

        $this->eventStore = new InMemoryEventStore($eventEmitter);
    }
}
