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

use Prooph\EventStore\Configuration\Configuration;
use Prooph\EventStore\EventStore;
use Prooph\EventStoreTest\Mock\User;
use Prooph\EventStoreTest\TestCase;

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

        $config = array(
            "adapter" => array(
                "Prooph\EventStore\Adapter\Zf2\Zf2EventStoreAdapter" => array(
                    'connection' => array(
                        'driver' => 'Pdo_Sqlite',
                        'database' => ':memory:'
                    )
                )
            ),
            "feature_manager" => array(
                "invokables" => array(
                    "eventlogger" => "Prooph\EventStoreTest\Mock\EventLoggerFeature",
                    "ProophEventSourcingFeature" => "Prooph\EventSourcing\EventStoreFeature\ProophEventSourcingFeature"
                )
            ),
            "features" => array(
                "eventlogger",
                "ProophEventSourcingFeature"
            )
        );

        $esConfig = new Configuration($config);

        $eventStore = new EventStore($esConfig);

        $eventStore->getAdapter()->createSchema(array("User"));

        $eventStore->beginTransaction();

        $user = new User("Alex");

        $eventStore->attach($user);

        $eventStore->commit();

        $loggedStreams = $esConfig->getFeatureManager()->get("eventlogger")->getLoggedStreams();

        $this->assertEquals(1, count($loggedStreams));
    }
}
 