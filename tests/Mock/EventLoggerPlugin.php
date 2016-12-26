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
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\Plugin\AbstractPlugin;

class EventLoggerPlugin extends AbstractPlugin
{
    /**
     * @var Iterator
     */
    protected $loggedStreamEvents;

    public function __construct()
    {
        $this->loggedStreamEvents = new \ArrayIterator();
    }

    public function attachToEventStore(ActionEventEmitterEventStore $eventStore): void
    {
        $this->listenerHandlers[] = $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_CREATE,
            function (ActionEvent $event): void {
                $stream = $event->getParam('stream');

                $this->loggedStreamEvents = $stream->streamEvents();
            },
            -10000
        );

        $this->listenerHandlers[] = $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_APPEND_TO,
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
