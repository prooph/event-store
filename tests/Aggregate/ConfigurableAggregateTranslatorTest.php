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

use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Aggregate\AggregateTranslator;
use Prooph\EventStore\Aggregate\AggregateTranslatorConfiguration;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Aggregate\ConfigurableAggregateTranslator;
use ProophTest\EventStore\Mock\CustomAggregateRoot;
use ProophTest\EventStore\Mock\CustomAggregateRootContract;
use ProophTest\EventStore\Mock\DefaultAggregateRoot;
use ProophTest\EventStore\Mock\DefaultAggregateRootContract;
use ProophTest\EventStore\Mock\FaultyAggregateRoot;
use ProophTest\EventStore\TestCase;

/**
 * Class ConfigurableAggregateTranslatorTest
 *
 * @package ProophTest\EventStore\Aggregate
 * @author Alexander Miertsch <contact@prooph.de>
 */
final class ConfigurableAggregateTranslatorTest extends TestCase
{
    /**
     * @var AggregateTranslatorConfiguration
     */
    private $configProphecy;

    protected function setUp()
    {
        parent::setUp();

        $config = $this->prophesize(AggregateTranslatorConfiguration::class);

        $config->versionMethodName()->willReturn(
            AggregateTranslatorConfiguration::DEFAULT_VERSION_METHOD_NAME
        );

        $config->identifierMethodName()->willReturn(
            AggregateTranslatorConfiguration::DEFAULT_IDENTIFIER_METHOD_NAME
        );

        $config->popRecordedEventsMethodName()->willReturn(
            AggregateTranslatorConfiguration::DEFAULT_POP_RECORDED_EVENTS_METHOD_NAME
        );

        $config->replayEventsMethodName()->willReturn(
            AggregateTranslatorConfiguration::DEFAULT_REPLAY_EVENTS_METHOD_NAME
        );

        $config->staticReconstituteFromHistoryMethodName()->willReturn(
            AggregateTranslatorConfiguration::DEFAULT_STATIC_RECONSTITUTE_FROM_HISTORY_METHOD_NAME
        );

        $config->eventToMessageCallback()->willReturn(
            AggregateTranslatorConfiguration::DEFAULT_EVENT_TO_MESSAGE_CALLBACK
        );

        $config->messageToEventCallback()->willReturn(
            AggregateTranslatorConfiguration::DEFAULT_MESSAGE_TO_EVENT_CALLBACK
        );

        $this->configProphecy = $config;
    }

    /**
     * @test
     */
    public function it_uses_default_method_name_to_get_the_version_from_aggregate_root()
    {
        $ar = $this->prophesize(DefaultAggregateRootContract::class);

        $ar->getVersion()->willReturn('123');

        $translator = new ConfigurableAggregateTranslator();

        $this->assertEquals('123', $translator->extractAggregateVersion($ar->reveal()));
    }

    /**
     * @test
     */
    public function it_uses_configured_method_name_to_get_the_version_from_aggregate_root()
    {
        $ar = $this->prophesize(CustomAggregateRootContract::class);

        $ar->version()->willReturn('123');

        $translator = new ConfigurableAggregateTranslator(null, 'version');

        $this->assertEquals('123', $translator->extractAggregateVersion($ar->reveal()));
    }

    /**
     * @test
     */
    public function it_uses_configured_via_config_method_name_to_get_the_version_from_aggregate_root()
    {
        $ar = $this->prophesize(CustomAggregateRootContract::class);

        $ar->version()->willReturn('123');

        $this->configProphecy->versionMethodName()->willReturn('version');

        $translator = ConfigurableAggregateTranslator::fromConfiguration($this->configProphecy->reveal());

        $this->assertEquals('123', $translator->extractAggregateVersion($ar->reveal()));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_version_method_name_to_be_a_string()
    {
        new ConfigurableAggregateTranslator(0);
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Aggregate\Exception\AggregateTranslationFailedException
     */
    public function it_throws_exception_if_version_method_does_not_exist()
    {
        $ar = $this->prophesize(CustomAggregateRootContract::class);

        $translator = new ConfigurableAggregateTranslator('unknownMethodName');

        $translator->extractAggregateVersion($ar->reveal());
    }


    /**
     * @test
     */
    public function it_uses_default_method_name_to_get_the_identifier_from_aggregate_root()
    {
        $ar = $this->prophesize(DefaultAggregateRootContract::class);

        $ar->getId()->willReturn('123');

        $translator = new ConfigurableAggregateTranslator();

        $this->assertEquals('123', $translator->extractAggregateId($ar->reveal()));
    }


    /**
     * @test
     */
    public function it_uses_configured_method_name_to_get_the_identifier_from_aggregate_root_if_injected()
    {
        $ar = $this->prophesize(CustomAggregateRootContract::class);

        $ar->identifier()->willReturn('123');

        $translator = new ConfigurableAggregateTranslator('identifier');

        $this->assertEquals('123', $translator->extractAggregateId($ar->reveal()));
    }

    /**
     * @test
     */
    public function it_uses_configured_via_config_method_name_to_get_the_identifier_from_aggregate_root_if_injected()
    {
        $ar = $this->prophesize(CustomAggregateRootContract::class);

        $ar->identifier()->willReturn('123');

        $this->configProphecy->identifierMethodName()->willReturn('identifier');

        $translator = ConfigurableAggregateTranslator::fromConfiguration($this->configProphecy->reveal());

        $this->assertEquals('123', $translator->extractAggregateId($ar->reveal()));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_identifier_method_name_to_be_a_string()
    {
        new ConfigurableAggregateTranslator(0);
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Aggregate\Exception\AggregateTranslationFailedException
     */
    public function it_throws_exception_if_identifier_method_does_not_exist()
    {
        $ar = $this->prophesize(CustomAggregateRootContract::class);

        $translator = new ConfigurableAggregateTranslator('unknownMethodName');

        $translator->extractAggregateId($ar->reveal());
    }

    /**
     * @test
     */
    public function it_uses_default_method_name_to_extract_pending_events()
    {
        $ar = $this->prophesize(DefaultAggregateRootContract::class);

        $domainEvent = $this->prophesize(Message::class);

        $domainEvents = [$domainEvent->reveal()];

        $ar->popRecordedEvents()->willReturn($domainEvents);

        $translator = new ConfigurableAggregateTranslator();

        $recordedEvents = $translator->extractPendingStreamEvents($ar->reveal());

        $this->assertSame($domainEvents, $recordedEvents);
    }

    /**
     * @test
     */
    public function it_uses_configured_method_name_to_extract_pending_events()
    {
        $ar = $this->prophesize(CustomAggregateRootContract::class);

        $domainEvent = $this->prophesize(Message::class);

        $domainEvents = [$domainEvent->reveal()];

        $ar->getPendingEvents()->willReturn($domainEvents);

        $translator = new ConfigurableAggregateTranslator(null, null, 'getPendingEvents');

        $recordedEvents = $translator->extractPendingStreamEvents($ar->reveal());

        $this->assertSame($domainEvents, $recordedEvents);
    }

    /**
     * @test
     */
    public function it_uses_configured_via_config_method_name_to_extract_pending_events()
    {
        $ar = $this->prophesize(CustomAggregateRootContract::class);

        $domainEvent = $this->prophesize(Message::class);

        $domainEvents = [$domainEvent->reveal()];

        $ar->getPendingEvents()->willReturn($domainEvents);

        $this->configProphecy->popRecordedEventsMethodName()->willReturn('getPendingEvents');

        $translator = ConfigurableAggregateTranslator::fromConfiguration($this->configProphecy->reveal());

        $recordedEvents = $translator->extractPendingStreamEvents($ar->reveal());

        $this->assertSame($domainEvents, $recordedEvents);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_pop_recorded_events_method_name_to_be_a_string()
    {
        new ConfigurableAggregateTranslator(null, null, 0);
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Aggregate\Exception\AggregateTranslationFailedException
     */
    public function it_throws_exception_if_pop_recorded_events_method_does_not_exist()
    {
        $ar = $this->prophesize(CustomAggregateRootContract::class);

        $translator = new ConfigurableAggregateTranslator(null, null, 'unknownMethod');

        $translator->extractPendingStreamEvents($ar->reveal());
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Aggregate\Exception\AggregateTranslationFailedException
     */
    public function it_throws_exception_if_apply_recorded_events_method_does_not_exist()
    {
        $ar = $this->prophesize(CustomAggregateRootContract::class);

        $translator = new ConfigurableAggregateTranslator(null, null, null, 'unknownMethod');

        $translator->replayStreamEvents($ar->reveal(), new \ArrayIterator([]));
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Aggregate\Exception\AggregateTranslationFailedException
     */
    public function it_throws_exception_if_pop_recored_evens_method_returns_invalid_message()
    {
        $ar = $this->prophesize(DefaultAggregateRootContract::class);

        $ar->popRecordedEvents()->willReturn([new \stdClass()]);

        $translator = new ConfigurableAggregateTranslator();

        $translator->extractPendingStreamEvents($ar->reveal());
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Aggregate\Exception\AggregateTranslationFailedException
     */
    public function it_throws_exception_if_apply_recored_evens_with_invalid_messages()
    {
        $ar = $this->prophesize(DefaultAggregateRootContract::class);

        $translator = new ConfigurableAggregateTranslator();

        $translator->replayStreamEvents($ar->reveal(), new \ArrayIterator([new \stdClass()]));
    }

    /**
     * @test
     */
    public function it_invokes_configured_event_to_message_callback_for_each_event_when_extracting()
    {
        $message = $this->prophesize(Message::class);

        $ar = $this->prophesize(DefaultAggregateRootContract::class);

        $ar->popRecordedEvents()->willReturn([new \stdClass(), new \stdClass()]);

        $translator = new ConfigurableAggregateTranslator(null, null, null, null, null, function (\stdClass $customEvent) use ($message) {
            return $message->reveal();
        });

        $recordedEvents = $translator->extractPendingStreamEvents($ar->reveal());

        $this->assertSame([$message->reveal(), $message->reveal()], $recordedEvents);
    }

    /**
     * @test
     */
    public function it_invokes_configured_via_config_event_to_message_callback_for_each_event_when_extracting()
    {
        $message = $this->prophesize(Message::class);

        $ar = $this->prophesize(DefaultAggregateRootContract::class);

        $ar->popRecordedEvents()->willReturn([new \stdClass(), new \stdClass()]);

        $this->configProphecy->eventToMessageCallback()->willReturn(function (\stdClass $customEvent) use ($message) {
            return $message->reveal();
        });

        $translator = ConfigurableAggregateTranslator::fromConfiguration($this->configProphecy->reveal());

        $recordedEvents = $translator->extractPendingStreamEvents($ar->reveal());

        $this->assertSame([$message->reveal(), $message->reveal()], $recordedEvents);
    }

    /**
     * @test
     */
    public function it_invokes_configured_message_to_event_callback_for_each_event_replaying()
    {
        $message = $this->prophesize(Message::class);

        $ar = $this->prophesize(DefaultAggregateRootContract::class);

        $dummyEvent = new \stdClass();

        $ar->replay($dummyEvent)->shouldBeCalled(2);

        $translator = new ConfigurableAggregateTranslator(null, null, null, null, null, null, function (Message $message) use ($dummyEvent) {
            return $dummyEvent;
        });

        $translator->replayStreamEvents($ar->reveal(), new \ArrayIterator([$message->reveal(), $message->reveal()]));
    }

    /**
     * @test
     */
    public function it_invokes_configured_via_config_message_to_event_callback_for_each_event_replaying()
    {
        $message = $this->prophesize(Message::class);

        $ar = $this->prophesize(DefaultAggregateRootContract::class);

        $dummyEvent = new \stdClass();

        $ar->replay($dummyEvent)->shouldBeCalled(2);

        $this->configProphecy->messageToEventCallback()->willReturn(function (Message $message) use ($dummyEvent) {
            return $dummyEvent;
        });


        $translator = ConfigurableAggregateTranslator::fromConfiguration($this->configProphecy->reveal());

        $translator->replayStreamEvents($ar->reveal(), new \ArrayIterator([$message->reveal(), $message->reveal()]));
    }


    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_event_to_message_callback_to_be_a_callable()
    {
        new ConfigurableAggregateTranslator(null, null, null, null, 0);
    }

    /**
     * @test
     */
    public function it_uses_default_method_name_to_reconstitute_an_aggregate_root_from_history()
    {
        $historyEvent = $this->prophesize(Message::class);

        $historyEvents = new \ArrayIterator([$historyEvent->reveal()]);

        $translator = new ConfigurableAggregateTranslator();

        $ar = $translator->reconstituteAggregateFromHistory(AggregateType::fromAggregateRootClass(DefaultAggregateRoot::class), $historyEvents);

        $this->assertSame(iterator_to_array($historyEvents), $ar->getHistoryEvents());
    }

    /**
     * @test
     */
    public function it_uses_configured_name_to_reconstitute_an_aggregate_root_from_history()
    {
        $historyEvent = $this->prophesize(Message::class);

        $historyEvents = new \ArrayIterator([$historyEvent->reveal()]);

        $translator = new ConfigurableAggregateTranslator(null, null, null, null, 'buildFromHistoryEvents');

        $ar = $translator->reconstituteAggregateFromHistory(AggregateType::fromAggregateRootClass(CustomAggregateRoot::class), $historyEvents);

        $this->assertSame($historyEvents, $ar->getHistoryEvents());
    }

    /**
     * @test
     */
    public function it_uses_configured_via_config_name_to_reconstitute_an_aggregate_root_from_history()
    {
        $historyEvent = $this->prophesize(Message::class);

        $historyEvents = new \ArrayIterator([$historyEvent->reveal()]);

        $this->configProphecy->staticReconstituteFromHistoryMethodName()->willReturn('buildFromHistoryEvents');

        $translator = ConfigurableAggregateTranslator::fromConfiguration($this->configProphecy->reveal());

        $ar = $translator->reconstituteAggregateFromHistory(AggregateType::fromAggregateRootClass(CustomAggregateRoot::class), $historyEvents);

        $this->assertSame($historyEvents, $ar->getHistoryEvents());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_reconstitute_form_history_method_name_to_be_a_string()
    {
        new ConfigurableAggregateTranslator(null, null, null, 0);
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Aggregate\Exception\AggregateTranslationFailedException
     */
    public function it_throws_exception_if_aggregate_root_does_not_exist()
    {
        $translator = new ConfigurableAggregateTranslator();

        $translator->reconstituteAggregateFromHistory(AggregateType::fromString('UnknownClass'), new \ArrayIterator([]));
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Aggregate\Exception\AggregateTranslationFailedException
     */
    public function it_throws_exception_if_reconstitute_form_history_method_name_does_not_exist()
    {
        $translator = new ConfigurableAggregateTranslator(null, null, null, null, 'unknownMethod');

        $translator->reconstituteAggregateFromHistory(AggregateType::fromString(DefaultAggregateRoot::class), new \ArrayIterator([]));
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Aggregate\Exception\AggregateTranslationFailedException
     */
    public function it_throws_exception_if_reconstitute_form_history_method_name_does_not_return_an_instance_of_aggregate_type()
    {
        $translator = new ConfigurableAggregateTranslator();

        $translator->reconstituteAggregateFromHistory(AggregateType::fromString(FaultyAggregateRoot::class), new \ArrayIterator([]));
    }

    /**
     * @test
     */
    public function it_uses_callback_to_convert_message_into_custom_domain_event()
    {
        $historyEvent = $this->prophesize(Message::class);

        $historyEvents = new \ArrayIterator([$historyEvent->reveal()]);

        $translator = new ConfigurableAggregateTranslator(null, null, null, null, null, null, function (Message $message) {
            return ['custom' => 'domainEvent'];
        });

        $ar = $translator->reconstituteAggregateFromHistory(AggregateType::fromAggregateRootClass(DefaultAggregateRoot::class), $historyEvents);

        $this->assertEquals([['custom' => 'domainEvent']], $ar->getHistoryEvents());
    }

    /**
     * @test
     */
    public function it_uses_callback_from_config_to_convert_message_into_custom_domain_event()
    {
        $historyEvent = $this->prophesize(Message::class);

        $historyEvents = new \ArrayIterator([$historyEvent->reveal()]);

        $this->configProphecy->messageToEventCallback()->willReturn(function (Message $message) {
            return ['custom' => 'domainEvent'];
        });

        $translator = ConfigurableAggregateTranslator::fromConfiguration($this->configProphecy->reveal());

        $ar = $translator->reconstituteAggregateFromHistory(AggregateType::fromAggregateRootClass(DefaultAggregateRoot::class), $historyEvents);

        $this->assertEquals([['custom' => 'domainEvent']], $ar->getHistoryEvents());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_forces_message_to_event_callback_to_be_a_callable()
    {
        new ConfigurableAggregateTranslator(null, null, null, null, null, 0);
    }

    /**
     * @test
     * @expectedException Prooph\EventStore\Aggregate\Exception\AggregateTranslationFailedException
     */
    public function it_fails_on_extracting_pending_stream_events_when_event_sourced_aggregate_root_is_not_an_object()
    {
        $translator = new ConfigurableAggregateTranslator();
        $translator->extractPendingStreamEvents('invalid');
    }

    /**
     * @test
     * @expectedException Prooph\EventStore\Aggregate\Exception\AggregateTranslationFailedException
     */
    public function it_fails_on_applying_pending_stream_events_when_event_sourced_aggregate_root_is_not_an_object()
    {
        $translator = new ConfigurableAggregateTranslator();
        $translator->replayStreamEvents('invalid', new \ArrayIterator([]));
    }

    /**
     * @test
     * @expectedException Prooph\EventStore\Aggregate\Exception\AggregateTranslationFailedException
     */
    public function it_fails_when_popped_recorded_events_are_not_an_array_or_traversable()
    {
        $ar = $this->prophesize(DefaultAggregateRootContract::class);

        $ar->popRecordedEvents()->willReturn('invalid');

        $translator = new ConfigurableAggregateTranslator();

        $translator->extractPendingStreamEvents($ar->reveal());
    }

    /**
     * @test
     */
    public function it_returns_a_properly_configured_instance_when_using_static_factory_method()
    {
        $translator = ConfigurableAggregateTranslator::fromConfiguration($this->configProphecy->reveal());
        $this->assertInstanceOf(AggregateTranslator::class, $translator);
    }
}
