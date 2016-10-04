<?php
/**
 * This file is part of the prooph/service-bus.
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

/**
 * Class EventLoggerFeature
 *
 * @package ProophTest\EventStore\Mock
 * @author Alexander Miertsch <contact@prooph.de>
 */
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
    public function setUp(EventStore $eventStore) : void
    {
        $eventStore->getActionEventEmitter()->attachListener('commit.post', [$this, "onPostCommit"]);
    }

    /**
     * @param ActionEvent $e
     */
    public function onPostCommit(ActionEvent $e) : void
    {
        $this->loggedStreamEvents = $e->getParam('recordedEvents', new \ArrayIterator());
    }

    public function getLoggedStreamEvents() : \Iterator
    {
        return $this->loggedStreamEvents;
    }
}
