<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Plugin;

use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\EventStore\ActionEventEmitterEventStoreTestCase;
use ProophTest\EventStore\Mock\EventLoggerPlugin;
use ProophTest\EventStore\Mock\UserCreated;
use Psr\Container\ContainerInterface;

class PluginManagerTest extends ActionEventEmitterEventStoreTestCase
{
    /**
     * @test
     */
    public function an_invokable_plugin_is_loaded_by_plugin_manager_and_attached_to_event_store_by_configuration(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('eventlogger')->willReturn(new EventLoggerPlugin())->shouldBeCalled();
        $container = $container->reveal();

        $logger = $container->get('eventlogger');
        $logger->attachToEventStore($this->eventStore);

        $this->eventStore->create(
            new Stream(
                new StreamName('user'),
                new \ArrayIterator([
                    UserCreated::with(
                        [
                            'name' => 'Alex',
                            'email' => 'contact@prooph.de',
                        ],
                        1
                    ),
                ])
            )
        );

        $loggedStreamEvents = $container->get('eventlogger')->getLoggedStreamEvents();

        $this->assertEquals(1, \count($loggedStreamEvents));
    }
}
