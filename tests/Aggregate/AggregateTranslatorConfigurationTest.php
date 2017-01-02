<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\EventStore\Aggregate;

use PHPUnit_Framework_TestCase as TestCase;
use Prooph\EventStore\Aggregate\AggregateTranslatorConfiguration;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Aggregate\AggregateTypeProvider;
use ProophTest\EventStore\Mock\Post;
use ProophTest\EventStore\Mock\User;

/**
 * Class AggregateTypeTest
 *
 * @package ProophTest\EventStore\Aggregate
 */
class AggregateTranslatorConfigurationTest extends TestCase
{
    /**
     * @test
     * @skipped
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_version_method_name_to_be_a_string()
    {
        $configuration = AggregateTranslatorConfiguration::createWithDefaults();
        $configuration->withVersionMethodName(0);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_identifier_method_name_to_be_a_string()
    {
        $configuration = AggregateTranslatorConfiguration::createWithDefaults();
        $configuration->withIdentifierMethodName(0);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_pop_recorded_events_method_name_to_be_a_string()
    {
        $configuration = AggregateTranslatorConfiguration::createWithDefaults();
        $configuration->withPopRecordedEventsMethodName(0);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_event_to_message_callback_to_be_a_callable()
    {
        $configuration = AggregateTranslatorConfiguration::createWithDefaults();
        $configuration->withEventToMessageCallback(0);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_reconstitute_form_history_method_name_to_be_a_string()
    {
        $configuration = AggregateTranslatorConfiguration::createWithDefaults();
        $configuration->withStaticReconstituteFromHistoryMethodName(0);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_message_to_event_callback_to_be_a_callable()
    {
        $configuration = AggregateTranslatorConfiguration::createWithDefaults();
        $configuration->withMessageToEventCallback(0);
    }

}