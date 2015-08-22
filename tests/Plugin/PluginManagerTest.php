<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 21.04.14 - 00:16
 */

namespace Prooph\EventStoreTest\Plugin;

use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\Adapter\InMemoryAdapter;
use Prooph\EventStore\Aggregate\AggregateRepository;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Aggregate\DefaultAggregateTranslator;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\AggregateStreamStrategy;
use Prooph\EventStoreTest\Mock\EventLoggerPlugin;
use Prooph\EventStoreTest\Mock\User;
use Prooph\EventStoreTest\TestCase;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

/**
 * Class FeatureManagerTest
 *
 * @package Prooph\EventStoreTest\Plugin
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class PluginManagerTest extends TestCase
{
    /**
     * @test
     */
    public function an_invokable_plugin_is_loaded_by_plugin_manager_and_attached_to_event_store_by_configuration()
    {
        $pluginManager = new ServiceManager(new Config([
            "invokables" => [
                "eventlogger" => EventLoggerPlugin::class,
            ]
        ]));

        $eventStore = new EventStore(new InMemoryAdapter(), new ProophActionEventEmitter());

        $logger = $pluginManager->get('eventlogger');
        $logger->setUp($eventStore);

        $repository = new AggregateRepository(
            $eventStore,
            AggregateType::fromAggregateRootClass('Prooph\EventStoreTest\Mock\User'),
            new DefaultAggregateTranslator(),
            new AggregateStreamStrategy($eventStore)
        );

        $eventStore->beginTransaction();

        $user = new User("Alex", "contact@prooph.de");

        $repository->addAggregateRoot($user);

        $eventStore->commit();

        $loggedStreamEvents = $pluginManager->get("eventlogger")->getLoggedStreamEvents();

        $this->assertEquals(1, count($loggedStreamEvents));
    }
}
