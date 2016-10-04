<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Aggregate;

use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Aggregate\ConfigurableAggregateTranslator;
use Prooph\EventStore\Aggregate\Exception\AggregateTranslationFailedException;
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
     * @test
     */
    public function it_uses_default_method_name_to_get_the_version_from_aggregate_root() : void
    {
        $ar = $this->prophesize(DefaultAggregateRootContract::class);

        $ar->getVersion()->willReturn('123');

        $translator = new ConfigurableAggregateTranslator();

        $this->assertEquals('123', $translator->extractAggregateVersion($ar->reveal()));
    }

    /**
     * @test
     */
    public function it_uses_configured_method_name_to_get_the_version_from_aggregate_root_if_injected() : void
    {
        $ar = $this->prophesize(CustomAggregateRootContract::class);

        $ar->version()->willReturn('123');

        $translator = new ConfigurableAggregateTranslator(null, 'version');

        $this->assertEquals('123', $translator->extractAggregateVersion($ar->reveal()));
    }

    /**
     * @test
     */
    public function it_throws_exception_if_version_method_does_not_exist() : void
    {
        $this->expectException(AggregateTranslationFailedException::class);

        $ar = $this->prophesize(CustomAggregateRootContract::class);

        $translator = new ConfigurableAggregateTranslator('unknownMethodName');

        $translator->extractAggregateVersion($ar->reveal());
    }


    /**
     * @test
     */
    public function it_uses_default_method_name_to_get_the_identifier_from_aggregate_root() : void
    {
        $ar = $this->prophesize(DefaultAggregateRootContract::class);

        $ar->getId()->willReturn('123');

        $translator = new ConfigurableAggregateTranslator();

        $this->assertEquals('123', $translator->extractAggregateId($ar->reveal()));
    }

    /**
     * @test
     */
    public function it_uses_configured_method_name_to_get_the_identifier_from_aggregate_root_if_injected() : void
    {
        $ar = $this->prophesize(CustomAggregateRootContract::class);

        $ar->identifier()->willReturn('123');

        $translator = new ConfigurableAggregateTranslator('identifier');

        $this->assertEquals('123', $translator->extractAggregateId($ar->reveal()));
    }

    /**
     * @test
     */
    public function it_throws_exception_if_identifier_method_does_not_exist() : void
    {
        $this->expectException(AggregateTranslationFailedException::class);

        $ar = $this->prophesize(CustomAggregateRootContract::class);

        $translator = new ConfigurableAggregateTranslator('unknownMethodName');

        $translator->extractAggregateId($ar->reveal());
    }

    /**
     * @test
     */
    public function it_uses_default_method_name_to_extract_pending_events() : void
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
    public function it_uses_configured_method_name_to_extract_pending_events() : void
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
    public function it_throws_exception_if_pop_recorded_events_method_does_not_exist() : void
    {
        $this->expectException(AggregateTranslationFailedException::class);

        $ar = $this->prophesize(CustomAggregateRootContract::class);

        $translator = new ConfigurableAggregateTranslator(null, null, 'unknownMethod');

        $translator->extractPendingStreamEvents($ar->reveal());
    }

    /**
     * @test
     */
    public function it_throws_exception_if_apply_recorded_events_method_does_not_exist() : void
    {
        $this->expectException(AggregateTranslationFailedException::class);

        $ar = $this->prophesize(CustomAggregateRootContract::class);

        $translator = new ConfigurableAggregateTranslator(null, null, null, 'unknownMethod');

        $translator->replayStreamEvents($ar->reveal(), new \ArrayIterator([]));
    }

    /**
     * @test
     */
    public function it_throws_exception_if_pop_recored_evens_method_returns_invalid_message() : void
    {
        $this->expectException(AggregateTranslationFailedException::class);

        $ar = $this->prophesize(DefaultAggregateRootContract::class);

        $ar->popRecordedEvents()->willReturn([new \stdClass()]);

        $translator = new ConfigurableAggregateTranslator();

        $translator->extractPendingStreamEvents($ar->reveal());
    }

    /**
     * @test
     */
    public function it_throws_exception_if_apply_recored_evens_with_invalid_messages() : void
    {
        $this->expectException(AggregateTranslationFailedException::class);

        $ar = $this->prophesize(DefaultAggregateRootContract::class);

        $translator = new ConfigurableAggregateTranslator();

        $translator->replayStreamEvents($ar->reveal(), new \ArrayIterator([new \stdClass()]));
    }

    /**
     * @test
     */
    public function it_invokes_event_to_message_callback_for_each_event_when_extracting() : void
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
    public function it_invokes_message_to_event_callback_for_each_event_replaying() : void
    {
        $message = $this->prophesize(Message::class);

        $ar = $this->prophesize(DefaultAggregateRootContract::class);

        $dummyEvent = new \stdClass();

        $ar->replay(new \ArrayIterator([$dummyEvent]))->shouldBeCalled(2);

        $translator = new ConfigurableAggregateTranslator(null, null, null, null, null, null, function (Message $message) use ($dummyEvent) {
            return $dummyEvent;
        });

        $translator->replayStreamEvents($ar->reveal(), new \ArrayIterator([$message->reveal(), $message->reveal()]));
    }

    /**
     * @test
     */
    public function it_uses_default_method_name_to_reconstitute_an_aggregate_root_from_history() : void
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
    public function it_uses_configured_name_to_reconstitute_an_aggregate_root_from_history() : void
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
    public function it_throws_exception_if_aggregate_root_does_not_exist() : void
    {
        $this->expectException(AggregateTranslationFailedException::class);

        $translator = new ConfigurableAggregateTranslator();

        $translator->reconstituteAggregateFromHistory(AggregateType::fromString('UnknownClass'), new \ArrayIterator([]));
    }

    /**
     * @test
     */
    public function it_throws_exception_if_reconstitute_form_history_method_name_does_not_exist() : void
    {
        $this->expectException(AggregateTranslationFailedException::class);

        $translator = new ConfigurableAggregateTranslator(null, null, null, null, 'unknownMethod');

        $translator->reconstituteAggregateFromHistory(AggregateType::fromString(DefaultAggregateRoot::class), new \ArrayIterator([]));
    }

    /**
     * @test
     */
    public function it_throws_exception_if_reconstitute_form_history_method_name_does_not_return_an_instance_of_aggregate_type() : void
    {
        $this->expectException(AggregateTranslationFailedException::class);

        $translator = new ConfigurableAggregateTranslator();

        $translator->reconstituteAggregateFromHistory(AggregateType::fromString(FaultyAggregateRoot::class), new \ArrayIterator([]));
    }

    /**
     * @test
     */
    public function it_uses_callback_to_convert_message_into_custom_domain_event() : void
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
    public function it_fails_on_extracting_pending_stream_events_when_event_sourced_aggregate_root_is_not_an_object() : void
    {
        $this->expectException(AggregateTranslationFailedException::class);

        $translator = new ConfigurableAggregateTranslator();
        $translator->extractPendingStreamEvents('invalid');
    }

    /**
     * @test
     */
    public function it_fails_on_applying_pending_stream_events_when_event_sourced_aggregate_root_is_not_an_object() : void
    {
        $this->expectException(AggregateTranslationFailedException::class);

        $translator = new ConfigurableAggregateTranslator();
        $translator->replayStreamEvents('invalid', new \ArrayIterator([]));
    }

    /**
     * @test
     */
    public function it_fails_when_popped_recorded_events_are_not_an_array_or_traversable() : void
    {
        $this->expectException(AggregateTranslationFailedException::class);

        $ar = $this->prophesize(DefaultAggregateRootContract::class);

        $ar->popRecordedEvents()->willReturn('invalid');

        $translator = new ConfigurableAggregateTranslator();

        $translator->extractPendingStreamEvents($ar->reveal());
    }
}
