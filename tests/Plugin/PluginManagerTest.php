<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 04/21/14 - 00:16 AM
 */

namespace ProophTest\EventStore\Plugin;

use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\Adapter\InMemoryAdapter;
use Prooph\EventStore\Aggregate\AggregateRepository;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Aggregate\ConfigurableAggregateTranslator;
use Prooph\EventStore\EventStore;
use ProophTest\EventStore\Mock\EventLoggerPlugin;
use ProophTest\EventStore\Mock\User;
use ProophTest\EventStore\TestCase;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

/**
 * Class FeatureManagerTest
 *
 * @package ProophTest\EventStore\Plugin
 * @author Alexander Miertsch <contact@prooph.de>
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
            AggregateType::fromAggregateRootClass('ProophTest\EventStore\Mock\User'),
            new ConfigurableAggregateTranslator(),
            null,
            null,
            true
        );

        $eventStore->beginTransaction();

        $user = User::create("Alex", "contact@prooph.de");

        $repository->addAggregateRoot($user);

        $eventStore->commit();

        $loggedStreamEvents = $pluginManager->get("eventlogger")->getLoggedStreamEvents();

        $this->assertEquals(1, count($loggedStreamEvents));
    }
}
