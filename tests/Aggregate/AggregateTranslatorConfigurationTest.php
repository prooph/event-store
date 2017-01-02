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
     * @var AggregateTranslatorConfiguration
     */
    private $config;

    protected function setUp()
    {
        parent::setUp();
        $this->config = AggregateTranslatorConfiguration::createWithDefaults();
    }

    /**
     * @test
     * @skipped
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_version_method_name_to_be_a_string()
    {
        $this->config->withVersionMethodName(0);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_identifier_method_name_to_be_a_string()
    {
        $this->config->withIdentifierMethodName(0);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_pop_recorded_events_method_name_to_be_a_string()
    {
        $this->config->withPopRecordedEventsMethodName(0);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_event_to_message_callback_to_be_a_callable()
    {
        $this->config->withEventToMessageCallback(0);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_reconstitute_form_history_method_name_to_be_a_string()
    {
        $this->config->withStaticReconstituteFromHistoryMethodName(0);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_message_to_event_callback_to_be_a_callable()
    {
        $this->config->withMessageToEventCallback(0);
    }

    /**
     * @test
     */
    public function it_ensures_setting_version_method_name_is_immutable()
    {
        $configuration2 = $this->config->withVersionMethodName('version');
        $this->assertNotSame($this->config, $configuration2);
        $this->assertEquals(
            AggregateTranslatorConfiguration::DEFAULT_VERSION_METHOD_NAME,
            $this->config->versionMethodName()
        );
    }

    /**
     * @test
     */
    public function it_ensures_setting_identifier_method_name_is_immutable()
    {
        $configuration2 = $this->config->withIdentifierMethodName('id');
        $this->assertNotSame($this->config, $configuration2);
        $this->assertEquals(
            AggregateTranslatorConfiguration::DEFAULT_IDENTIFIER_METHOD_NAME,
            $this->config->identifierMethodName()
        );
    }

    /**
     * @test
     */
    public function it_ensures_setting_pop_recorded_events_method_name_is_immutable()
    {
        $configuration2 = $this->config->withPopRecordedEventsMethodName('pop');
        $this->assertNotSame($this->config, $configuration2);
        $this->assertEquals(
            AggregateTranslatorConfiguration::DEFAULT_POP_RECORDED_EVENTS_METHOD_NAME,
            $this->config->popRecordedEventsMethodName()
        );
    }

    /**
     * @test
     */
    public function it_ensures_setting_replay_events_method_name_is_immutable()
    {
        $configuration2 = $this->config->replayEventsMethodName('replayAllTheThings');
        $this->assertNotSame($this->config, $configuration2);
        $this->assertEquals(
            AggregateTranslatorConfiguration::DEFAULT_REPLAY_EVENTS_METHOD_NAME,
            $this->config->replayEventsMethodName()
        );
    }

    /**
     * @test
     */
    public function it_ensures_setting_static_reconstitute_from_history_method_name_is_immutable()
    {
        $configuration2 = $this->config->withStaticReconstituteFromHistoryMethodName('reconstitute');
        $this->assertNotSame($this->config, $configuration2);
        $this->assertEquals(
            AggregateTranslatorConfiguration::DEFAULT_STATIC_RECONSTITUTE_FROM_HISTORY_METHOD_NAME,
            $this->config->staticReconstituteFromHistoryMethodName()
        );
    }

    /**
     * @test
     */
    public function it_ensures_setting_event_to_message_callback_is_immutable()
    {
        $configuration2 = $this->config->withEventToMessageCallback(function () {});
        $this->assertNotSame($this->config, $configuration2);
        $this->assertEquals(
            AggregateTranslatorConfiguration::DEFAULT_EVENT_TO_MESSAGE_CALLBACK,
            $this->config->eventToMessageCallback()
        );
    }

    /**
     * @test
     */
    public function it_ensures_setting_message_to_event_callback_is_immutable()
    {
        $configuration2 = $this->config->withMessageToEventCallback(function () {});
        $this->assertNotSame($this->config, $configuration2);
        $this->assertEquals(
            AggregateTranslatorConfiguration::DEFAULT_MESSAGE_TO_EVENT_CALLBACK,
            $this->config->messageToEventCallback()
        );
    }

    /**
     * @test
     */
    public function it_returns_configured_version_method_name()
    {
        $configuration = $this->config->withVersionMethodName('version');
        $this->assertEquals('version', $configuration->versionMethodName());
    }

    /**
     * @test
     */
    public function it_returns_configured_identifier_method_name()
    {
        $configuration = $this->config->withIdentifierMethodName('id');
        $this->assertEquals('id', $configuration->identifierMethodName());
    }

    /**
     * @test
     */
    public function it_returns_configured_pop_recorded_events_method_name()
    {
        $configuration = $this->config->withPopRecordedEventsMethodName('pop');
        $this->assertEquals('pop', $configuration->popRecordedEventsMethodName());
    }

    /**
     * @test
     */
    public function it_returns_configured_replay_events_method_name()
    {
        $configuration = $this->config->withReplayEventsMethodName('replayAllTheThings');
        $this->assertEquals('replayAllTheThings', $configuration->replayEventsMethodName());
    }

    /**
     * @test
     */
    public function it_returns_configured_static_reconstitute_from_history_method_name()
    {
        $configuration = $this->config->withStaticReconstituteFromHistoryMethodName('reconstitute');
        $this->assertEquals('reconstitute', $configuration->staticReconstituteFromHistoryMethodName());
    }

    /**
     * @test
     */
    public function it_returns_configured_event_to_message_callback()
    {
        $callback = function () {};

        $configuration = $this->config->withEventToMessageCallback($callback);
        $this->assertSame($callback, $configuration->eventToMessageCallback());
    }

    /**
     * @test
     */
    public function it_returns_configured_message_to_event_callback()
    {
        $callback = function () {};

        $configuration = $this->config->withMessageToEventCallback($callback);
        $this->assertSame($callback, $configuration->messageToEventCallback());
    }

    /**
     * @test
     */
    public function it_returns_default_value_for_version_method_name()
    {
        $this->assertEquals(
            AggregateTranslatorConfiguration::DEFAULT_VERSION_METHOD_NAME,
            $this->config->versionMethodName()
        );
    }

    /**
     * @test
     */
    public function it_returns_default_value_for_identifier_method_name()
    {
        $this->assertEquals(
            AggregateTranslatorConfiguration::DEFAULT_IDENTIFIER_METHOD_NAME,
            $this->config->identifierMethodName()
        );
    }

    /**
     * @test
     */
    public function it_returns_default_value_for_pop_recorded_events_method_name()
    {
        $this->assertEquals(
            AggregateTranslatorConfiguration::DEFAULT_POP_RECORDED_EVENTS_METHOD_NAME,
            $this->config->popRecordedEventsMethodName()
        );
    }
    
    /**
     * @test
     */
    public function it_returns_default_value_for_replay_events_method_name()
    {
        $this->assertEquals(
            AggregateTranslatorConfiguration::DEFAULT_REPLAY_EVENTS_METHOD_NAME,
            $this->config->replayEventsMethodName()
        );
    }

    /**
     * @test
     */
    public function it_returns_default_value_for_static_reconstitute_from_history_method_name()
    {
        $this->assertEquals(
            AggregateTranslatorConfiguration::DEFAULT_STATIC_RECONSTITUTE_FROM_HISTORY_METHOD_NAME,
            $this->config->staticReconstituteFromHistoryMethodName()
        );
    }

    /**
     * @test
     */
    public function it_returns_default_value_for_event_to_message_callback()
    {
        $this->assertEquals(
            AggregateTranslatorConfiguration::DEFAULT_EVENT_TO_MESSAGE_CALLBACK,
            $this->config->eventToMessageCallback()
        );
    }

    /**
     * @test
     */
    public function it_returns_default_value_for_message_to_event_callback()
    {
        $this->assertEquals(
            AggregateTranslatorConfiguration::DEFAULT_MESSAGE_TO_EVENT_CALLBACK,
            $this->config->messageToEventCallback()
        );
    }
}