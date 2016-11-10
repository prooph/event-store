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

namespace ProophTest\EventStore\Metadata;

use Prooph\Common\Event\DefaultListenerHandler;
use Prooph\Common\Event\ListenerHandler;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\TestCase;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\DefaultActionEvent;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Metadata\MetadataEnricher;
use Prooph\EventStore\Metadata\MetadataEnricherPlugin;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use Prophecy\Argument;

class MetadataEnricherPluginTest extends TestCase
{
    /**
     * @test
     */
    public function it_attaches_itself_to_event_store_events(): void
    {
        $metadataEnricher = $this->prophesize(MetadataEnricher::class);
        $eventStore = $this->prophesize(EventStore::class);
        $eventEmitter = $this->prophesize(ActionEventEmitter::class);

        $createStreamListener = null;
        $appendToStreamListener = null;

        $eventStore->getActionEventEmitter()->willReturn($eventEmitter);
        $eventEmitter->attachListener('create.pre', Argument::any(), -1000)->will(
            $function = function ($args) use (&$createStreamListener, &$function): ListenerHandler {
                $createStreamListener = $args[1];
                return new DefaultListenerHandler($function);
            }
        );
        $eventEmitter->attachListener('appendTo.pre', Argument::any(), -1000)->will(
            $function = function ($args) use (&$appendToStreamListener, &$function): ListenerHandler {
                $appendToStreamListener = $args[1];
                return new DefaultListenerHandler($function);
            }
        );

        $plugin = new MetadataEnricherPlugin($metadataEnricher->reveal());

        $plugin->setUp($eventStore->reveal());

        $this->assertEquals([$plugin, 'onEventStoreCreateStream'], $createStreamListener);
        $this->assertEquals([$plugin, 'onEventStoreAppendToStream'], $appendToStreamListener);
    }

    /**
     * @test
     */
    public function it_enrich_metadata_on_stream_create(): void
    {
        $metadataEnricher = $this->prophesize(MetadataEnricher::class);
        $plugin = new MetadataEnricherPlugin($metadataEnricher->reveal());

        $messageEvent = UserCreated::with(['name' => 'Test'], 1);
        $stream = new Stream(new StreamName('test'), new \ArrayIterator([$messageEvent]));
        $actionEvent = new DefaultActionEvent('create.pre');
        $actionEvent->setParam('stream', $stream);

        $metadataEnricher->enrich($messageEvent)->willReturn(
            $messageEvent->withAddedMetadata('foo', 'bar')
        );

        $plugin->onEventStoreCreateStream($actionEvent);

        // Assertion on event in the stream
        $streamEvents = $actionEvent->getParam('stream')->streamEvents();
        $this->assertCount(1, $streamEvents);
        $this->assertEquals($messageEvent->payload(), $streamEvents[0]->payload());
        $this->assertEquals($messageEvent->version(), $streamEvents[0]->version());
        $this->assertEquals($messageEvent->createdAt(), $streamEvents[0]->createdAt());
        $this->assertEquals(['foo' => 'bar', '_aggregate_version' => 1], $streamEvents[0]->metadata());
    }

    /**
     * @test
     */
    public function it_does_not_enrich_metadata_on_create_if_stream_is_not_set(): void
    {
        $metadataEnricher = $this->prophesize(MetadataEnricher::class);
        $metadataEnricher->enrich(Argument::any())->shouldNotBeCalled();

        $actionEvent = new DefaultActionEvent('create.pre');

        $plugin = new MetadataEnricherPlugin($metadataEnricher->reveal());
        $plugin->onEventStoreCreateStream($actionEvent);
    }

    /**
     * @test
     */
    public function it_enrich_metadata_on_stream_appendTo(): void
    {
        $metadataEnricher = $this->prophesize(MetadataEnricher::class);
        $plugin = new MetadataEnricherPlugin($metadataEnricher->reveal());

        $messageEvent = UserCreated::with(['name' => 'Test'], 1);
        $actionEvent = new DefaultActionEvent('appendTo.pre');
        $actionEvent->setParam('streamEvents', new \ArrayIterator([$messageEvent]));

        $metadataEnricher->enrich($messageEvent)->willReturn(
            $messageEvent->withAddedMetadata('foo', 'bar')
        );

        $plugin->onEventStoreAppendToStream($actionEvent);

        // Assertion on event in the stream
        $streamEvents = $actionEvent->getParam('streamEvents');
        $this->assertCount(1, $streamEvents);
        $this->assertEquals($messageEvent->payload(), $streamEvents[0]->payload());
        $this->assertEquals($messageEvent->version(), $streamEvents[0]->version());
        $this->assertEquals($messageEvent->createdAt(), $streamEvents[0]->createdAt());
        $this->assertEquals(['foo' => 'bar', '_aggregate_version' => 1], $streamEvents[0]->metadata());
    }


    /**
     * @test
     */
    public function it_does_not_enrich_metadata_on_appendTo_if_stream_is_not_set(): void
    {
        $metadataEnricher = $this->prophesize(MetadataEnricher::class);
        $metadataEnricher->enrich(Argument::any())->shouldNotBeCalled();

        $actionEvent = new DefaultActionEvent('appendTo.pre');

        $plugin = new MetadataEnricherPlugin($metadataEnricher->reveal());
        $plugin->onEventStoreAppendToStream($actionEvent);
    }
}
