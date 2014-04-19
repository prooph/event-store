<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStoreTest;

use Prooph\EventStore\Adapter\AdapterInterface;
use Prooph\EventStore\Adapter\Zf2\Zf2EventStoreAdapter;
use Prooph\EventStore\Configuration\Configuration;
use Prooph\EventStore\EventStore;

/**
 * TestCase for Prooph EventStore tests
 *
 * @author Alexander Miertsch <contact@prooph.de>
 * @package Prooph\EventStoreTest
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var AdapterInterface
     */
    protected $eventStoreAdapter;

    /**
     * @var EventStore
     */
    protected $eventStore;

    protected function tearDown()
    {
        $this->getTestEventStore()->clear();
    }
    
    protected function initEventStoreAdapter()
    {
        $options = array(
            'connection' => array(
                'driver' => 'Pdo_Sqlite',
                'database' => ':memory:'
            )
        );
        
        $this->eventStoreAdapter = new Zf2EventStoreAdapter($options);
    }
    
    /**
     * @return AdapterInterface
     */
    protected function getEventStoreAdapter()
    {
        if (is_null($this->eventStoreAdapter)) {
            $this->initEventStoreAdapter();
        }

        return $this->eventStoreAdapter;
    }

    /**
     * @return EventStore
     */
    protected function getTestEventStore()
    {
        if(is_null($this->eventStore)) {
            $config = new Configuration();
            $config->setAdapter($this->getEventStoreAdapter());
            $this->eventStore = new EventStore($config);
        }

        return $this->eventStore;
    }
}
