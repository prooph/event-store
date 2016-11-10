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

use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Plugin\Plugin;

class EventLoggerPlugin implements Plugin
{
    /**
     * @var \Iterator
     */
    protected $loggedStreamEvents;

    public function __construct()
    {
        $this->loggedStreamEvents = new \ArrayIterator();
    }

    /**
     * @param EventStore $eventStore
     * @return void
     */
    public function setUp(EventStore $eventStore): void
    {
        $callable = \Closure::fromCallable([$this, 'on']);

        $eventStore->getActionEventEmitter()->attachListener('create', $callable, -10000);
        $eventStore->getActionEventEmitter()->attachListener('appendTo', $callable, -10000);
    }

    /**
     * @param ActionEvent $e
     */
    private function on(ActionEvent $e): void
    {
        $this->loggedStreamEvents = $e->getParam('streamEvents', new \ArrayIterator());
    }

    public function getLoggedStreamEvents(): \Iterator
    {
        return $this->loggedStreamEvents;
    }
}
