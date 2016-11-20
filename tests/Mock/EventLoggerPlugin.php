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

namespace ProophTest\EventStore\Mock;

use Iterator;
use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\ActionEventEmitterAwareEventStore;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Plugin\Plugin;

class EventLoggerPlugin implements Plugin
{
    /**
     * @var Iterator
     */
    protected $loggedStreamEvents;

    public function __construct()
    {
        $this->loggedStreamEvents = new \ArrayIterator();
    }

    public function setUp(EventStore $eventStore): void
    {
        if (! $eventStore instanceof ActionEventEmitterAwareEventStore) {
            throw new InvalidArgumentException(
                sprintf(
                    'EventStore must implement %s',
                    ActionEventEmitterAwareEventStore::class
                )
            );
        }

        $eventStore->getActionEventEmitter()->attachListener(
            ActionEventEmitterAwareEventStore::EVENT_CREATE,
            function (ActionEvent $event): void {
                $stream = $event->getParam('stream');

                $this->loggedStreamEvents = $stream->streamEvents();
            },
            -10000
        );

        $eventStore->getActionEventEmitter()->attachListener(
            ActionEventEmitterAwareEventStore::EVENT_APPEND_TO,
            function (ActionEvent $event): void {
                $this->loggedStreamEvents = $event->getParam('streamEvents', new \ArrayIterator());
            },
            -10000
        );
    }

    public function getLoggedStreamEvents(): Iterator
    {
        return $this->loggedStreamEvents;
    }
}
