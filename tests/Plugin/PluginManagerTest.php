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

namespace ProophTest\EventStore\Plugin;

use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\Adapter\InMemoryAdapter;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use ProophTest\EventStore\Mock\EventLoggerPlugin;
use ProophTest\EventStore\Mock\User;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\TestCase;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

class PluginManagerTest extends TestCase
{
    /**
     * @test
     */
    public function an_invokable_plugin_is_loaded_by_plugin_manager_and_attached_to_event_store_by_configuration(): void
    {
        $pluginManager = new ServiceManager(new Config([
            "invokables" => [
                "eventlogger" => EventLoggerPlugin::class,
            ]
        ]));

        $eventStore = new EventStore(new InMemoryAdapter(), new ProophActionEventEmitter());

        $logger = $pluginManager->get('eventlogger');
        $logger->setUp($eventStore);

        $eventStore->beginTransaction();

        $eventStore->create(
            new Stream(
                new StreamName('user'),
                new \ArrayIterator([
                    UserCreated::with(
                        [
                            'name' => 'Alex',
                            'email' => 'contact@prooph.de'
                        ],
                        1
                    )
                ])
            )
        );

        $user = User::create("Alex", "contact@prooph.de");

        $eventStore->commit();

        $loggedStreamEvents = $pluginManager->get("eventlogger")->getLoggedStreamEvents();

        $this->assertEquals(1, count($loggedStreamEvents));
    }
}
