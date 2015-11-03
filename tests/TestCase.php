<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\EventStore;

use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\Adapter\InMemoryAdapter;
use Prooph\EventStore\EventStore;

/**
 * TestCase for Prooph EventStore tests
 *
 * @author Alexander Miertsch <contact@prooph.de>
 * @package ProophTest\EventStore
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventStore
     */
    protected $eventStore;

    protected function setUp()
    {
        $inMemoryAdapter = new InMemoryAdapter();
        $eventEmitter    = new ProophActionEventEmitter();

        $this->eventStore = new EventStore($inMemoryAdapter, $eventEmitter);
    }
}
