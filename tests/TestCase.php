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
use Prooph\EventStore\CanControlTransactionActionEventEmitterAware;
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
            CanControlTransactionActionEventEmitterAware::EVENT_APPEND_TO,
            CanControlTransactionActionEventEmitterAware::EVENT_CREATE,
            CanControlTransactionActionEventEmitterAware::EVENT_LOAD,
            CanControlTransactionActionEventEmitterAware::EVENT_LOAD_REVERSE,
            CanControlTransactionActionEventEmitterAware::EVENT_DELETE,
            CanControlTransactionActionEventEmitterAware::EVENT_HAS_STREAM,
            CanControlTransactionActionEventEmitterAware::EVENT_FETCH_STREAM_METADATA,
            CanControlTransactionActionEventEmitterAware::EVENT_BEGIN_TRANSACTION,
            CanControlTransactionActionEventEmitterAware::EVENT_COMMIT,
            CanControlTransactionActionEventEmitterAware::EVENT_ROLLBACK,
        ]);

        $this->eventStore = new InMemoryEventStore($eventEmitter);
    }
}
