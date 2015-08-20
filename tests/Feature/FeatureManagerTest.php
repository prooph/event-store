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

namespace Prooph\EventStoreTest\Feature;

use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\Adapter\InMemoryAdapter;
use Prooph\EventStore\Aggregate\AggregateRepository;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Aggregate\DefaultAggregateTranslator;
use Prooph\EventStore\Configuration\Configuration;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\AggregateStreamStrategy;
use Prooph\EventStoreTest\Mock\EventLoggerFeature;
use Prooph\EventStoreTest\Mock\User;
use Prooph\EventStoreTest\TestCase;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

/**
 * Class FeatureManagerTest
 *
 * @package Prooph\EventStoreTest\Feature
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class FeatureManagerTest extends TestCase
{
    /**
     * @test
     */
    public function an_invokable_feature_is_loaded_by_feature_manager_and_attached_to_event_store_by_configuration()
    {
        $featureManager = new ServiceManager(new Config([
            "invokables" => [
                "eventlogger" => EventLoggerFeature::class,
            ]
        ]));

        $eventStore = new EventStore(new InMemoryAdapter(), new ProophActionEventEmitter());

        $logger = $featureManager->get('eventlogger');
        $logger->setUp($eventStore);

        $repository = new AggregateRepository(
            $eventStore,
            new DefaultAggregateTranslator(),
            new AggregateStreamStrategy($eventStore),
            AggregateType::fromAggregateRootClass('Prooph\EventStoreTest\Mock\User')
        );

        $eventStore->beginTransaction();

        $user = new User("Alex", "contact@prooph.de");

        $repository->addAggregateRoot($user);

        $eventStore->commit();

        $loggedStreamEvents = $featureManager->get("eventlogger")->getLoggedStreamEvents();

        $this->assertEquals(1, count($loggedStreamEvents));
    }
}
 