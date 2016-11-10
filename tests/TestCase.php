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
use Prooph\EventStore\ActionEventEmitterAware;
use Prooph\EventStore\EventStore;
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
            ActionEventEmitterAware::EVENT_APPEND_TO,
            ActionEventEmitterAware::EVENT_APPEND_TO,
            ActionEventEmitterAware::EVENT_CREATE,
            ActionEventEmitterAware::EVENT_LOAD,
            ActionEventEmitterAware::EVENT_LOAD_EVENTS,
            ActionEventEmitterAware::EVENT_LOAD_REVERSE,
        ]);

        $this->eventStore = new InMemoryEventStore($eventEmitter);
    }
}
